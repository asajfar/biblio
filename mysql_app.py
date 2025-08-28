from flask import Flask, render_template, request, redirect, url_for, flash, session, send_file
import mysql.connector
from mysql.connector import Error
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

# MySQL konfiguracija
MYSQL_CONFIG = {
    'host': os.environ.get('MYSQL_HOST', 'localhost'),
    'port': int(os.environ.get('MYSQL_PORT', 3306)),
    'user': os.environ.get('MYSQL_USER', 'svecani_prijemi'),
    'password': os.environ.get('MYSQL_PASSWORD', 'password123'),
    'database': os.environ.get('MYSQL_DATABASE', 'svecani_prijemi'),
    'charset': 'utf8mb4',
    'collation': 'utf8mb4_unicode_ci',
    'autocommit': True
}

def get_db_connection():
    """Kreiranje konekcije sa MySQL bazom"""
    try:
        connection = mysql.connector.connect(**MYSQL_CONFIG)
        return connection
    except Error as e:
        print(f"Greška pri konekciji sa bazom: {e}")
        return None

def init_database():
    """Kreiranje tabela u MySQL bazi podataka"""
    connection = get_db_connection()
    if not connection:
        print("Nema konekcije sa bazom!")
        return
    
    cursor = connection.cursor()
    
    try:
        # Kreiranje baze ako ne postoji
        cursor.execute(f"CREATE DATABASE IF NOT EXISTS {MYSQL_CONFIG['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        cursor.execute(f"USE {MYSQL_CONFIG['database']}")
        
        # Tabela korisnika
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(80) UNIQUE NOT NULL,
                email VARCHAR(120) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ''')
        
        # Tabela prijema
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS receptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                date DATE NOT NULL,
                venue VARCHAR(200) NOT NULL,
                description TEXT,
                user_id INT NOT NULL,
                public_id VARCHAR(100) UNIQUE,
                is_public BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_public_id (public_id),
                INDEX idx_date (date)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ''')
        
        # Tabela stolova
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS tables (
                id INT AUTO_INCREMENT PRIMARY KEY,
                number INT NOT NULL,
                capacity INT NOT NULL DEFAULT 8,
                reception_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reception_id) REFERENCES receptions(id) ON DELETE CASCADE,
                UNIQUE KEY unique_table_number (reception_id, number),
                INDEX idx_reception_id (reception_id)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ''')
        
        # Tabela gostiju
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS guests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(120),
                notes TEXT,
                reception_id INT NOT NULL,
                table_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (reception_id) REFERENCES receptions(id) ON DELETE CASCADE,
                FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
                INDEX idx_reception_id (reception_id),
                INDEX idx_table_id (table_id),
                INDEX idx_name (name)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ''')
        
        connection.commit()
        print("Baza podataka je uspešno inicijalizovana!")
        
    except Error as e:
        print(f"Greška pri kreiranju tabela: {e}")
        connection.rollback()
    finally:
        cursor.close()
        connection.close()

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
        connection = get_db_connection()
        if not connection:
            flash('Greška pri konekciji sa bazom!', 'danger')
            return render_template('index.html')
        
        cursor = connection.cursor(dictionary=True)
        cursor.execute('''
            SELECT * FROM receptions WHERE user_id = %s ORDER BY date DESC
        ''', (session['user_id'],))
        receptions = cursor.fetchall()
        cursor.close()
        connection.close()
        return render_template('dashboard.html', receptions=receptions)
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        password = request.form['password']
        
        connection = get_db_connection()
        if not connection:
            flash('Greška pri konekciji sa bazom!', 'danger')
            return render_template('register.html')
        
        cursor = connection.cursor()
        
        try:
            # Proveri da li korisnik već postoji
            cursor.execute(
                'SELECT id FROM users WHERE username = %s OR email = %s',
                (username, email)
            )
            existing_user = cursor.fetchone()
            
            if existing_user:
                flash('Korisničko ime ili email već postoji!', 'danger')
                return render_template('register.html')
            
            # Kreiraj novog korisnika
            password_hash = hash_password(password)
            cursor.execute(
                'INSERT INTO users (username, email, password_hash) VALUES (%s, %s, %s)',
                (username, email, password_hash)
            )
            connection.commit()
            
            flash('Registracija uspešna! Možete se prijaviti.', 'success')
            return redirect(url_for('login'))
            
        except Error as e:
            flash(f'Greška pri registraciji: {str(e)}', 'danger')
            connection.rollback()
        finally:
            cursor.close()
            connection.close()
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        connection = get_db_connection()
        if not connection:
            flash('Greška pri konekciji sa bazom!', 'danger')
            return render_template('login.html')
        
        cursor = connection.cursor(dictionary=True)
        cursor.execute(
            'SELECT * FROM users WHERE username = %s',
            (username,)
        )
        user = cursor.fetchone()
        cursor.close()
        connection.close()
        
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
        
        connection = get_db_connection()
        if not connection:
            flash('Greška pri konekciji sa bazom!', 'danger')
            return render_template('create_reception.html')
        
        cursor = connection.cursor()
        try:
            cursor.execute(
                'INSERT INTO receptions (name, date, venue, description, user_id, public_id) VALUES (%s, %s, %s, %s, %s, %s)',
                (name, date, venue, description, session['user_id'], public_id)
            )
            reception_id = cursor.lastrowid
            connection.commit()
            
            flash('Svečani prijem je uspešno kreiran!', 'success')
            return redirect(url_for('reception_detail', id=reception_id))
        except Error as e:
            flash(f'Greška pri kreiranju prijema: {str(e)}', 'danger')
            connection.rollback()
        finally:
            cursor.close()
            connection.close()
    
    return render_template('create_reception.html')

# Nastavak aplikacije u sledećem delu...

if __name__ == '__main__':
    init_database()
    app.run(debug=True, host='0.0.0.0', port=5000)