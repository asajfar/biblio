from flask import Flask, render_template, request, redirect, url_for, flash, session
from flask_sqlalchemy import SQLAlchemy
from flask_bcrypt import Bcrypt
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from datetime import datetime
import os

app = Flask(__name__)
app.config['SECRET_KEY'] = 'your-secret-key-change-this'
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///svecani_prijem.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)
bcrypt = Bcrypt(app)
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'
login_manager.login_message = 'Molimo vas da se prijavite da biste pristupili ovoj stranici.'
login_manager.login_message_category = 'info'

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# Database Models
class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(128))
    date_created = db.Column(db.DateTime, default=datetime.utcnow)
    receptions = db.relationship('Reception', backref='organizer', lazy=True)

class Reception(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    date = db.Column(db.Date, nullable=False)
    venue = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    tables = db.relationship('Table', backref='reception', lazy=True, cascade='all, delete-orphan')
    guests = db.relationship('Guest', backref='reception', lazy=True, cascade='all, delete-orphan')

class Table(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    number = db.Column(db.Integer, nullable=False)
    capacity = db.Column(db.Integer, nullable=False, default=8)
    reception_id = db.Column(db.Integer, db.ForeignKey('reception.id'), nullable=False)
    guests = db.relationship('Guest', backref='table', lazy=True)

class Guest(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    phone = db.Column(db.String(20))
    email = db.Column(db.String(120))
    notes = db.Column(db.Text)
    reception_id = db.Column(db.Integer, db.ForeignKey('reception.id'), nullable=False)
    table_id = db.Column(db.Integer, db.ForeignKey('table.id'))

# Routes
@app.route('/')
def index():
    if current_user.is_authenticated:
        receptions = Reception.query.filter_by(user_id=current_user.id).order_by(Reception.date.desc()).all()
        return render_template('dashboard.html', receptions=receptions)
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        password = request.form['password']
        
        # Proveri da li korisnik već postoji
        if User.query.filter_by(username=username).first():
            flash('Korisničko ime već postoji!', 'danger')
            return render_template('register.html')
        
        if User.query.filter_by(email=email).first():
            flash('Email adresa je već registrovana!', 'danger')
            return render_template('register.html')
        
        # Kreiraj novog korisnika
        password_hash = bcrypt.generate_password_hash(password).decode('utf-8')
        user = User(username=username, email=email, password_hash=password_hash)
        
        db.session.add(user)
        db.session.commit()
        
        flash('Registracija uspešna! Možete se prijaviti.', 'success')
        return redirect(url_for('login'))
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        user = User.query.filter_by(username=username).first()
        
        if user and bcrypt.check_password_hash(user.password_hash, password):
            login_user(user)
            next_page = request.args.get('next')
            flash(f'Dobrodošli, {user.username}!', 'success')
            return redirect(next_page) if next_page else redirect(url_for('index'))
        else:
            flash('Neispravno korisničko ime ili lozinka!', 'danger')
    
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('Uspešno ste se odjavili!', 'info')
    return redirect(url_for('index'))

@app.route('/create_reception', methods=['GET', 'POST'])
@login_required
def create_reception():
    if request.method == 'POST':
        name = request.form['name']
        date = datetime.strptime(request.form['date'], '%Y-%m-%d').date()
        venue = request.form['venue']
        description = request.form.get('description', '')
        
        reception = Reception(
            name=name,
            date=date,
            venue=venue,
            description=description,
            user_id=current_user.id
        )
        
        db.session.add(reception)
        db.session.commit()
        
        flash('Svečani prijem je uspešno kreiran!', 'success')
        return redirect(url_for('reception_detail', id=reception.id))
    
    return render_template('create_reception.html')

@app.route('/reception/<int:id>')
@login_required
def reception_detail(id):
    reception = Reception.query.get_or_404(id)
    
    # Proveri da li je korisnik vlasnik
    if reception.user_id != current_user.id:
        flash('Nemate dozvolu za pristup ovom prijemu!', 'danger')
        return redirect(url_for('index'))
    
    return render_template('reception_detail.html', reception=reception)

@app.route('/reception/<int:id>/add_table', methods=['POST'])
@login_required
def add_table(id):
    reception = Reception.query.get_or_404(id)
    
    if reception.user_id != current_user.id:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    table_number = int(request.form['table_number'])
    capacity = int(request.form.get('capacity', 8))
    
    # Proveri da li stol već postoji
    existing_table = Table.query.filter_by(reception_id=id, number=table_number).first()
    if existing_table:
        flash(f'Stol broj {table_number} već postoji!', 'danger')
    else:
        table = Table(number=table_number, capacity=capacity, reception_id=id)
        db.session.add(table)
        db.session.commit()
        flash(f'Stol broj {table_number} je dodat!', 'success')
    
    return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/add_guest', methods=['POST'])
@login_required
def add_guest(id):
    reception = Reception.query.get_or_404(id)
    
    if reception.user_id != current_user.id:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    name = request.form['name']
    phone = request.form.get('phone', '')
    email = request.form.get('email', '')
    notes = request.form.get('notes', '')
    table_id = request.form.get('table_id')
    
    guest = Guest(
        name=name,
        phone=phone,
        email=email,
        notes=notes,
        reception_id=id,
        table_id=int(table_id) if table_id else None
    )
    
    db.session.add(guest)
    db.session.commit()
    
    flash(f'Gost {name} je dodat!', 'success')
    return redirect(url_for('reception_detail', id=id))

@app.route('/reception/<int:id>/seating_chart')
@login_required
def seating_chart(id):
    reception = Reception.query.get_or_404(id)
    
    if reception.user_id != current_user.id:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    tables = Table.query.filter_by(reception_id=id).order_by(Table.number).all()
    unassigned_guests = Guest.query.filter_by(reception_id=id, table_id=None).all()
    
    return render_template('seating_chart.html', reception=reception, tables=tables, unassigned_guests=unassigned_guests)

@app.route('/move_guest', methods=['POST'])
@login_required
def move_guest():
    guest_id = int(request.form['guest_id'])
    table_id = request.form.get('table_id')
    
    guest = Guest.query.get_or_404(guest_id)
    reception = guest.reception
    
    if reception.user_id != current_user.id:
        flash('Nemate dozvolu!', 'danger')
        return redirect(url_for('index'))
    
    guest.table_id = int(table_id) if table_id else None
    db.session.commit()
    
    return redirect(url_for('seating_chart', id=reception.id))

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    app.run(debug=True)