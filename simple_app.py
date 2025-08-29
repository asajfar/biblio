from flask import Flask, render_template, request, redirect, url_for, flash, session, send_file
import sqlite3
import bcrypt
import secrets
from datetime import datetime
import os
from PIL import Image, ImageDraw, ImageFont
import qrcode
import io
import math

app = Flask(__name__)
app.config['SECRET_KEY'] = 'your-secret-key-change-this'

DATABASE = 'svecani_prijem.db'

def get_db_connection():
    conn = sqlite3.connect(DATABASE)
    conn.row_factory = sqlite3.Row
    return conn

def init_database():
    """Kreiranje tabela u bazi podataka"""
    conn = get_db_connection()
    
    # Tabela korisnika
    conn.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Tabela prijema
    conn.execute('''
        CREATE TABLE IF NOT EXISTS receptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            date DATE NOT NULL,
            venue TEXT NOT NULL,
            description TEXT,
            user_id INTEGER NOT NULL,
            public_id TEXT UNIQUE,
            is_public INTEGER DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )
    ''')
    
    # Tabela stolova
    conn.execute('''
        CREATE TABLE IF NOT EXISTS tables (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            number INTEGER NOT NULL,
            capacity INTEGER NOT NULL DEFAULT 8,
            reception_id INTEGER NOT NULL,
            FOREIGN KEY (reception_id) REFERENCES receptions (id)
        )
    ''')
    
    # Tabela gostiju
    conn.execute('''
        CREATE TABLE IF NOT EXISTS guests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            notes TEXT,
            reception_id INTEGER NOT NULL,
            table_id INTEGER,
            FOREIGN KEY (reception_id) REFERENCES receptions (id),
            FOREIGN KEY (table_id) REFERENCES tables (id)
        )
    ''')
    
    conn.commit()
    conn.close()

def hash_password(password):
    """Hash lozinke"""
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())

def check_password(password, hashed):
    """Proverava lozinku"""
    return bcrypt.checkpw(password.encode('utf-8'), hashed)

def create_gradient_background(width, height, color1=(135, 206, 235), color2=(25, 25, 112)):
    """Kreiranje gradient pozadine"""
    image = Image.new('RGB', (width, height))
    draw = ImageDraw.Draw(image)
    
    for y in range(height):
        # Interpolacija između boja
        ratio = y / height
        r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
        g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
        b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
        draw.line([(0, y), (width, y)], fill=(r, g, b))
    
    return image

def get_font(size):
    """Dobij font ili koristi default"""
    try:
        # Pokušaj da učitaš sistemski font
        return ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", size)
    except:
        try:
            return ImageFont.truetype("arial.ttf", size)
        except:
            return ImageFont.load_default()

def generate_seating_chart_image(reception_data, tables_data):
    """Generiši sliku rasporeda sedenja"""
    
    # Dimenzije slike
    width, height = 1200, 1600
    
    # Kreiranje pozadine
    img = create_gradient_background(width, height, (240, 248, 255), (100, 149, 237))
    draw = ImageDraw.Draw(img)
    
    # Dodavanje dekorativnih elemenata
    # Zvezde ili tačke na pozadini
    for i in range(50):
        x = secrets.randbelow(width)
        y = secrets.randbelow(height)
        draw.ellipse([x-2, y-2, x+2, y+2], fill=(255, 255, 255, 100))
    
    # Header sa informacijama o prijemu
    header_height = 200
    
    # Naslov
    title_font = get_font(48)
    title = reception_data['name']
    title_bbox = draw.textbbox((0, 0), title, font=title_font)
    title_width = title_bbox[2] - title_bbox[0]
    draw.text(((width - title_width) // 2, 30), title, fill='white', font=title_font)
    
    # Datum i mesto
    info_font = get_font(24)
    date_text = f"📅 {reception_data['date']}"
    venue_text = f"📍 {reception_data['venue']}"
    
    date_bbox = draw.textbbox((0, 0), date_text, font=info_font)
    venue_bbox = draw.textbbox((0, 0), venue_text, font=info_font)
    
    date_width = date_bbox[2] - date_bbox[0]
    venue_width = venue_bbox[2] - venue_bbox[0]
    
    draw.text(((width - date_width) // 2, 100), date_text, fill='white', font=info_font)
    draw.text(((width - venue_width) // 2, 135), venue_text, fill='white', font=info_font)
    
    # Linija za separaciju
    draw.line([(50, header_height - 20), (width - 50, header_height - 20)], fill='white', width=3)
    
    # Raspored stolova
    current_y = header_height + 20
    table_font = get_font(20)
    guest_font = get_font(16)
    
    # Sortiraj stolove po brojovima
    sorted_tables = sorted(tables_data, key=lambda t: t['number'])
    
    # Podeli stolove u kolone
    tables_per_column = 3
    column_width = width // 2
    
    for i, table in enumerate(sorted_tables):
        # Računaj poziciju
        column = i % 2
        row = i // 2
        
        x_start = 50 + (column * column_width)
        y_start = current_y + (row * 200)
        
        # Pozadina za stol
        table_bg_color = (255, 255, 255, 220)
        table_rect = [x_start, y_start, x_start + column_width - 100, y_start + 180]
        
        # Skrugli uglove za lepši izgled
        draw.rounded_rectangle(table_rect, radius=15, fill=table_bg_color)
        draw.rounded_rectangle(table_rect, radius=15, outline=(70, 130, 180), width=2)
        
        # Naslov stola
        table_title = f"🪑 STOL {table['number']}"
        capacity_info = f"({len(table['guests'])}/{table['capacity']} mesta)"
        
        table_title_bbox = draw.textbbox((0, 0), table_title, font=table_font)
        table_title_width = table_title_bbox[2] - table_title_bbox[0]
        
        # Pozicioniraj naslov stola
        title_x = x_start + (column_width - 100 - table_title_width) // 2
        draw.text((title_x, y_start + 10), table_title, fill=(25, 25, 112), font=table_font)
        
        capacity_bbox = draw.textbbox((0, 0), capacity_info, font=guest_font)
        capacity_width = capacity_bbox[2] - capacity_bbox[0]
        capacity_x = x_start + (column_width - 100 - capacity_width) // 2
        draw.text((capacity_x, y_start + 35), capacity_info, fill=(70, 130, 180), font=guest_font)
        
        # Lista gostiju
        guest_y = y_start + 65
        if table['guests']:
            for j, guest in enumerate(table['guests'][:8]):  # Max 8 gostiju po stolu
                guest_text = f"• {guest['name']}"
                draw.text((x_start + 15, guest_y), guest_text, fill=(25, 25, 112), font=guest_font)
                guest_y += 20
                
            if len(table['guests']) > 8:
                draw.text((x_start + 15, guest_y), f"... i još {len(table['guests']) - 8} gostiju", 
                         fill=(128, 128, 128), font=guest_font)
        else:
            draw.text((x_start + 15, guest_y), "Nema gostiju", fill=(128, 128, 128), font=guest_font)
    
    # Footer sa dodatnim informacijama
    footer_y = height - 100
    footer_font = get_font(18)
    total_guests = sum(len(table['guests']) for table in tables_data)
    total_capacity = sum(table['capacity'] for table in tables_data)
    
    stats_text = f"📊 Ukupno: {total_guests}/{total_capacity} gostiju • {len(tables_data)} stolova"
    stats_bbox = draw.textbbox((0, 0), stats_text, font=footer_font)
    stats_width = stats_bbox[2] - stats_bbox[0]
    draw.text(((width - stats_width) // 2, footer_y), stats_text, fill='white', font=footer_font)
    
    # Dodaj watermark
    watermark_text = "Kreano sa ❤️ - Svečani Prijemi"
    watermark_font = get_font(14)
    watermark_bbox = draw.textbbox((0, 0), watermark_text, font=watermark_font)
    watermark_width = watermark_bbox[2] - watermark_bbox[0]
    draw.text(((width - watermark_width) // 2, height - 30), watermark_text, 
             fill=(255, 255, 255, 150), font=watermark_font)
    
    return img

def generate_qr_code(url):
    """Generiši QR kod za javni link"""
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=4,
    )
    qr.add_data(url)
    qr.make(fit=True)
    
    return qr.make_image(fill_color="black", back_color="white")

@app.route('/')
def index():
    if 'user_id' in session:
        conn = get_db_connection()
        receptions = conn.execute('''
            SELECT * FROM receptions WHERE user_id = ? ORDER BY date DESC
        ''', (session['user_id'],)).fetchall()
        conn.close()
        return render_template('dashboard.html', receptions=receptions)
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        password = request.form['password']
        
        conn = get_db_connection()
        
        # Proveri da li korisnik već postoji
        existing_user = conn.execute(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            (username, email)
        ).fetchone()
        
        if existing_user:
            flash('Korisničko ime ili email već postoji!', 'danger')
            conn.close()
            return render_template('register.html')
        
        # Kreiraj novog korisnika
        password_hash = hash_password(password)
        conn.execute(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
            (username, email, password_hash)
        )
        conn.commit()
        conn.close()
        
        flash('Registracija uspešna! Možete se prijaviti.', 'success')
        return redirect(url_for('login'))
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        conn = get_db_connection()
        user = conn.execute(
            'SELECT * FROM users WHERE username = ?',
            (username,)
        ).fetchone()
        conn.close()
        
        if user and check_password(password, user['password_hash']):
            session['user_id'] = user['id']
            session['username'] = user['username']
            flash(f'Dobrodošli, {user["username"]}!', 'success')
            return redirect(url_for('index'))
        else:
            flash('Neispravno korisničko ime ili lozinka!', 'danger')
    
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Uspešno ste se odjavili!', 'info')
    return redirect(url_for('index'))

@app.route('/create_reception', methods=['GET', 'POST'])
def create_reception():
    if 'user_id' not in session:
        flash('Molimo vas da se prijavite!', 'warning')
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        name = request.form['name']
        date = request.form['date']
        venue = request.form['venue']
        description = request.form.get('description', '')
        
        # Generiši javni ID za prijem
        public_id = secrets.token_urlsafe(16)
        
        conn = get_db_connection()
        cursor = conn.execute(
            'INSERT INTO receptions (name, date, venue, description, user_id, public_id) VALUES (?, ?, ?, ?, ?, ?)',
            (name, date, venue, description, session['user_id'], public_id)
        )
        reception_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        flash('Svečani prijem je uspešno kreiran!', 'success')
        return redirect(url_for('reception_detail', id=reception_id))
    
    return render_template('create_reception.html')

@app.route('/reception/<int:id>')
def reception_detail(id):
    if 'user_id' not in session:
        flash('Molimo vas da se prijavite!', 'warning')
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Prijem nije pronađen!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    tables = conn.execute(
        'SELECT * FROM tables WHERE reception_id = ? ORDER BY number',
        (id,)
    ).fetchall()
    
    guests = conn.execute(
        'SELECT * FROM guests WHERE reception_id = ?',
        (id,)
    ).fetchall()
    
    # Dodaj broj gostiju po stolu
    tables_with_guests = []
    for table in tables:
        guest_count = conn.execute(
            'SELECT COUNT(*) as count FROM guests WHERE table_id = ?',
            (table['id'],)
        ).fetchone()['count']
        
        tables_with_guests.append({
            'id': table['id'],
            'number': table['number'],
            'capacity': table['capacity'],
            'guest_count': guest_count
        })
    
    conn.close()
    
    return render_template('reception_detail.html', 
                         reception=reception, 
                         tables=tables_with_guests, 
                         guests=guests)

@app.route('/reception/<int:id>/add_table', methods=['POST'])
def add_table(id):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    table_number = int(request.form['table_number'])
    capacity = int(request.form.get('capacity', 8))
    
    conn = get_db_connection()
    
    # Proveri vlasništvo
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    # Proveri da li stol već postoji
    existing_table = conn.execute(
        'SELECT id FROM tables WHERE reception_id = ? AND number = ?',
        (id, table_number)
    ).fetchone()
    
    if existing_table:
        flash(f'Stol broj {table_number} već postoji!', 'danger')
    else:
        conn.execute(
            'INSERT INTO tables (number, capacity, reception_id) VALUES (?, ?, ?)',
            (table_number, capacity, id)
        )
        conn.commit()
        flash(f'Stol broj {table_number} je dodat!', 'success')
    
    conn.close()
    return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/add_guest', methods=['POST'])
def add_guest(id):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    name = request.form['name']
    phone = request.form.get('phone', '')
    email = request.form.get('email', '')
    notes = request.form.get('notes', '')
    table_id = request.form.get('table_id')
    
    conn = get_db_connection()
    
    # Proveri vlasništvo
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    conn.execute(
        'INSERT INTO guests (name, phone, email, notes, reception_id, table_id) VALUES (?, ?, ?, ?, ?, ?)',
        (name, phone, email, notes, id, table_id if table_id else None)
    )
    conn.commit()
    conn.close()
    
    flash(f'Gost {name} je dodat!', 'success')
    return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/seating_chart')
def seating_chart(id):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    # Uzmi stolove sa gostima
    tables = conn.execute(
        'SELECT * FROM tables WHERE reception_id = ? ORDER BY number',
        (id,)
    ).fetchall()
    
    tables_with_guests = []
    for table in tables:
        guests = conn.execute(
            'SELECT * FROM guests WHERE table_id = ?',
            (table['id'],)
        ).fetchall()
        
        tables_with_guests.append({
            'id': table['id'],
            'number': table['number'],
            'capacity': table['capacity'],
            'guests': guests
        })
    
    # Gosti bez stola
    unassigned_guests = conn.execute(
        'SELECT * FROM guests WHERE reception_id = ? AND table_id IS NULL',
        (id,)
    ).fetchall()
    
    conn.close()
    
    return render_template('seating_chart.html', 
                         reception=reception,
                         tables=tables_with_guests,
                         unassigned_guests=unassigned_guests)

@app.route('/move_guest', methods=['POST'])
def move_guest():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    guest_id = int(request.form['guest_id'])
    table_id = request.form.get('table_id')
    
    conn = get_db_connection()
    
    # Proveri vlasništvo gosta
    guest = conn.execute(
        '''SELECT g.*, r.user_id 
           FROM guests g 
           JOIN receptions r ON g.reception_id = r.id 
           WHERE g.id = ?''',
        (guest_id,)
    ).fetchone()
    
    if not guest or guest['user_id'] != session['user_id']:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    conn.execute(
        'UPDATE guests SET table_id = ? WHERE id = ?',
        (table_id if table_id else None, guest_id)
    )
    conn.commit()
    conn.close()
    
    return redirect(url_for('seating_chart', id=guest['reception_id']))

@app.route('/public/<public_id>')
def public_seating_chart(public_id):
    """Javna stranica za pregled rasporeda sedenja"""
    conn = get_db_connection()
    
    # Pronađi prijem po javnom ID-ju
    reception = conn.execute(
        'SELECT * FROM receptions WHERE public_id = ? AND is_public = 1',
        (public_id,)
    ).fetchone()
    
    if not reception:
        conn.close()
        return render_template('public_not_found.html'), 404
    
    # Uzmi stolove sa gostima
    tables = conn.execute(
        'SELECT * FROM tables WHERE reception_id = ? ORDER BY number',
        (reception['id'],)
    ).fetchall()
    
    tables_with_guests = []
    for table in tables:
        guests = conn.execute(
            'SELECT * FROM guests WHERE table_id = ? ORDER BY name',
            (table['id'],)
        ).fetchall()
        
        tables_with_guests.append({
            'id': table['id'],
            'number': table['number'],
            'capacity': table['capacity'],
            'guests': guests
        })
    
    # Svi gosti za pretragu
    all_guests = conn.execute(
        '''SELECT g.*, t.number as table_number FROM guests g 
           LEFT JOIN tables t ON g.table_id = t.id 
           WHERE g.reception_id = ? ORDER BY g.name''',
        (reception['id'],)
    ).fetchall()
    
    conn.close()
    
    return render_template('public_seating_chart.html', 
                         reception=reception,
                         tables=tables_with_guests,
                         all_guests=all_guests)

@app.route('/reception/<int:id>/toggle_public', methods=['POST'])
def toggle_public_access(id):
    """Uključi/isključi javni pristup prijemu"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    # Uključi/isključi javni pristup
    new_status = 1 if not reception['is_public'] else 0
    conn.execute(
        'UPDATE receptions SET is_public = ? WHERE id = ?',
        (new_status, id)
    )
    conn.commit()
    conn.close()
    
    if new_status:
        flash('Javni pristup je uključen! Gosti mogu pristupiti rasporedu.', 'success')
    else:
        flash('Javni pristup je isključen.', 'info')
    
    return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/public_link')
def get_public_link(id):
    """Dobij javni link za prijem"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    conn.close()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    public_url = url_for('public_seating_chart', public_id=reception['public_id'], _external=True)
    
    return render_template('public_link.html', 
                         reception=reception,
                         public_url=public_url)

@app.route('/reception/<int:id>/generate_image')
def generate_image(id):
    """Generiši sliku rasporeda sedenja"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    # Proveri vlasništvo
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    # Uzmi stolove sa gostima
    tables = conn.execute(
        'SELECT * FROM tables WHERE reception_id = ? ORDER BY number',
        (id,)
    ).fetchall()
    
    tables_with_guests = []
    for table in tables:
        guests = conn.execute(
            'SELECT * FROM guests WHERE table_id = ? ORDER BY name',
            (table['id'],)
        ).fetchall()
        
        tables_with_guests.append({
            'id': table['id'],
            'number': table['number'],
            'capacity': table['capacity'],
            'guests': guests
        })
    
    conn.close()
    
    # Generiši sliku
    try:
        img = generate_seating_chart_image(reception, tables_with_guests)
        
        # Sačuvaj sliku u memoriji
        img_io = io.BytesIO()
        img.save(img_io, 'PNG', quality=95)
        img_io.seek(0)
        
        filename = f"raspored_{reception['name'].replace(' ', '_')}_{reception['date']}.png"
        
        return send_file(
            img_io,
            mimetype='image/png',
            as_attachment=True,
            download_name=filename
        )
        
    except Exception as e:
        flash(f'Greška pri generisanju slike: {str(e)}', 'danger')
        return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/generate_qr')
def generate_qr_image(id):
    """Generiši QR kod za javni link"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    conn.close()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    if not reception['is_public']:
        flash('Javni pristup mora biti uključen za QR kod!', 'warning')
        return redirect(url_for('reception_detail', id=id))
    
    try:
        public_url = url_for('public_seating_chart', public_id=reception['public_id'], _external=True)
        qr_img = generate_qr_code(public_url)
        
        # Sačuvaj QR kod u memoriji
        img_io = io.BytesIO()
        qr_img.save(img_io, 'PNG')
        img_io.seek(0)
        
        filename = f"qr_kod_{reception['name'].replace(' ', '_')}.png"
        
        return send_file(
            img_io,
            mimetype='image/png',
            as_attachment=True,
            download_name=filename
        )
        
    except Exception as e:
        flash(f'Greška pri generisanju QR koda: {str(e)}', 'danger')
        return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/canva_template')
def canva_template(id):
    """Preusmeri na Canva sa parametrima"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = get_db_connection()
    
    reception = conn.execute(
        'SELECT * FROM receptions WHERE id = ? AND user_id = ?',
        (id, session['user_id'])
    ).fetchone()
    
    if not reception:
        flash('Nemate dozvolu!', 'danger')
        conn.close()
        return redirect(url_for('index'))
    
    # Pripremi podatke za Canva template
    tables = conn.execute(
        'SELECT * FROM tables WHERE reception_id = ? ORDER BY number',
        (id,)
    ).fetchall()
    
    conn.close()
    
    return render_template('canva_template.html', 
                         reception=reception,
                         tables=tables)

if __name__ == '__main__':
    init_database()
    app.run(debug=True, host='0.0.0.0', port=5000)