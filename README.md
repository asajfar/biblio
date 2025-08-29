# Svečani Prijemi - Web Aplikacija

Kompletna aplikacija za upravljanje svečanim prizemima i rasporedom mesta za goste.

## Funkcionalnosti

- 🔐 **Registracija i prijava korisnika**
- 🎉 **Kreiranje svečanih prijema**
- 🪑 **Upravljanje stolovima i kapacitetom**
- 👥 **Dodavanje i upravljanje gostima**
- 📊 **Pregled rasporeda mesta po stolovima**
- 🌐 **Javna stranica za goste sa pretragom**
- 🔗 **Deljenje javnog linka sa gostima**
- 🎨 **Generisanje slika rasporeda sedenja**
- 📱 **QR kodovi za brz pristup**
- 🎨 **Canva integracija za kreiranje vizuelnih materijala**
- 📱 **Responzivni dizajn za sve uređaje**

## Instalacija i pokretanje

### Opcija 1: Sa Docker-om (Preporučeno)

#### 1. Instalirajte Docker i Docker Compose
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install docker.io docker-compose

# Ili preuzmite sa docker.com
```

#### 2. Pokrenite MySQL sa Docker-om
```bash
# Klonirajte repozitorijum
git clone <repository-url>
cd svecani-prijemi

# Pokrenite MySQL i phpMyAdmin
docker-compose up -d

# Proverite da li su kontejneri pokrenuti
docker-compose ps
```

#### 3. Instalirajte Python zavisnosti
```bash
pip install -r requirements.txt
```

#### 4. Pokrenite aplikaciju
```bash
python mysql_app.py
```

### Opcija 2: Sa lokalnim MySQL-om

#### 1. Instalirajte MySQL
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install mysql-server

# CentOS/RHEL
sudo yum install mysql-server

# macOS
brew install mysql
```

#### 2. Konfigurirajte MySQL
```bash
# Pokrenite MySQL
sudo systemctl start mysql

# Bezbednosno podešavanje
sudo mysql_secure_installation

# Prijavite se kao root
mysql -u root -p

# Pokrenite setup script
mysql -u root -p < mysql_setup.sql
```

#### 3. Kreirajte environment fajl
```bash
cp .env.example .env
# Uredite .env fajl sa vašim MySQL podacima
```

#### 4. Instalirajte Python zavisnosti
```bash
pip install -r requirements.txt
```

#### 5. Pokrenite aplikaciju
```bash
python mysql_app.py
```

### Opcija 3: Sa SQLite (Jednostavna)

```bash
pip install --break-system-packages Flask bcrypt Pillow qrcode
python simple_app.py
```

## Pristup aplikaciji

- **Web aplikacija**: `http://localhost:5000`
- **phpMyAdmin** (ako koristite Docker): `http://localhost:8080`

## MySQL konfiguracija

```bash
# Default podaci za Docker
Host: localhost
Port: 3306
Database: svecani_prijemi
Username: svecani_prijemi
Password: password123
```

## Korišćenje

### 1. Registracija
- Idite na stranicu registracije
- Unesite korisničko ime, email i lozinku
- Prijavite se sa vašim podacima

### 2. Kreiranje svečanog prijema
- Kliknite na "Novi Prijem"
- Unesite naziv, datum, mesto i opis
- Sačuvajte prijem

### 3. Dodavanje stolova
- Otvorite detalje prijema
- Kliknite "Dodaj Stol"
- Unesite broj stola i kapacitet

### 4. Dodavanje gostiju
- Kliknite "Dodaj Gosta"
- Unesite podatke o gostu
- Možete odmah dodeliti stol ili to uraditi kasnije

### 5. Upravljanje rasporedom
- Idite na "Pregled Rasporeda"
- Vidite sve stolove sa gostima
- Premeštajte goste između stolova
- Pratite statistike zaposednutosti

### 6. Javna stranica za goste
- Uključite javni pristup u upravljanju prijemom
- Kopirajte javni link
- Podelite link sa gostima
- Gosti mogu da pretražuju svoja imena i vide na kom stolu sede

### 7. Generisanje slika i materijala
- Automatsko kreiranje PNG slika rasporeda
- QR kodovi za javnu stranicu
- Canva template integracija
- Download funkcionalnost za goste

### 8. Deljenje sa gostima
- Email integracija za slanje linkova
- WhatsApp deljenje
- Kopiranje linka u clipboard
- QR kod generisanje

## Struktura aplikacije

```
svecani-prijemi/
├── mysql_app.py          # MySQL Flask aplikacija (GLAVNA)
├── simple_app.py         # SQLite Flask aplikacija (backup)
├── requirements.txt      # Python zavisnosti
├── README.md            # Dokumentacija
├── .env.example         # Environment varijable
├── mysql_setup.sql      # MySQL setup script
├── docker-compose.yml   # Docker konfiguracija
├── templates/           # HTML šabloni
│   ├── base.html
│   ├── index.html
│   ├── login.html
│   ├── register.html
│   ├── dashboard.html
│   ├── create_reception.html
│   ├── reception_detail.html
│   ├── seating_chart.html
│   ├── public_seating_chart.html  # Javna stranica za goste
│   ├── public_not_found.html      # Greška stranica
│   ├── public_link.html           # Upravljanje javnim linkom
│   └── canva_template.html        # Canva integracija
└── static/              # Statički fajlovi
    ├── css/
    │   └── style.css
    └── js/
        └── script.js
```

## Database modeli (MySQL)

### users
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `username` VARCHAR(80) UNIQUE NOT NULL
- `email` VARCHAR(120) UNIQUE NOT NULL  
- `password_hash` VARCHAR(255) NOT NULL
- `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### receptions
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(200) NOT NULL
- `date` DATE NOT NULL
- `venue` VARCHAR(200) NOT NULL
- `description` TEXT
- `user_id` INT NOT NULL (FK)
- `public_id` VARCHAR(100) UNIQUE
- `is_public` BOOLEAN DEFAULT FALSE
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### tables
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `number` INT NOT NULL
- `capacity` INT NOT NULL DEFAULT 8
- `reception_id` INT NOT NULL (FK)
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- UNIQUE constraint na (reception_id, number)

### guests
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(200) NOT NULL
- `phone` VARCHAR(20)
- `email` VARCHAR(120)
- `notes` TEXT
- `reception_id` INT NOT NULL (FK)
- `table_id` INT NULL (FK)
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

## Tehnologije

- **Backend**: Python Flask
- **Database**: MySQL 8.0 (PyMySQL connector)
- **Container**: Docker & Docker Compose
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Security**: bcrypt za hash-ovanje lozinki
- **Image Generation**: PIL/Pillow, QRCode

## Sigurnost

- Lozinke su hash-ovane sa bcrypt
- Sesije su sigurne sa SECRET_KEY
- Javni linkovi su bezbedni (token-based pristup)
- Validacija korisničkih unosa
- Kontrola pristupa (samo vlasnici mogu upravljati svojim prijemima)

## Produkcija

Za produkciju preporučuje se:

1. Promena SECRET_KEY u app.py
2. Korišćenje PostgreSQL ili MySQL umesto SQLite
3. Dodavanje HTTPS
4. Korišćenie WSGI servera (Gunicorn, uWSGI)
5. Konfiguracija nginx-a za statičke fajlove

## Licenca

MIT License - možete slobodno koristiti i menjati kod.