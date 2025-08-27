# Svečani Prijemi - Web Aplikacija

Aplikacija za upravljanje svečanim prizemima i rasporedom mesta za goste.

## Funkcionalnosti

- 🔐 **Registracija i prijava korisnika**
- 🎉 **Kreiranje svečanih prijema**
- 🪑 **Upravljanje stolovima i kapacitetom**
- 👥 **Dodavanje i upravljanje gostima**
- 📊 **Pregled rasporeda mesta po stolovima**
- 📱 **Responzivni dizajn za sve uređaje**

## Instalacija i pokretanje

### 1. Klonirajte repozitorijum ili preuzmite fajlove

```bash
git clone <repository-url>
cd svecani-prijemi
```

### 2. Kreirajte virtuelno okruženje (preporučeno)

```bash
python -m venv venv

# Na Windows:
venv\Scripts\activate

# Na Linux/Mac:
source venv/bin/activate
```

### 3. Instalirajte potrebne pakete

```bash
pip install -r requirements.txt
```

### 4. Pokrenite aplikaciju

```bash
python app.py
```

Aplikacija će biti dostupna na `http://localhost:5000`

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

## Struktura aplikacije

```
svecani-prijemi/
├── app.py                 # Glavna Flask aplikacija
├── requirements.txt       # Python zavisnosti
├── README.md             # Dokumentacija
├── templates/            # HTML šabloni
│   ├── base.html
│   ├── index.html
│   ├── login.html
│   ├── register.html
│   ├── dashboard.html
│   ├── create_reception.html
│   ├── reception_detail.html
│   └── seating_chart.html
└── static/              # Statički fajlovi
    ├── css/
    │   └── style.css
    └── js/
        └── script.js
```

## Database modeli

- **User**: Korisnici aplikacije
- **Reception**: Svečani prijemi
- **Table**: Stolovi u sali
- **Guest**: Gosti na prijemu

## Tehnologije

- **Backend**: Python Flask
- **Database**: SQLite (SQLAlchemy ORM)
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Authentication**: Flask-Login, Flask-Bcrypt

## Sigurnost

- Lozinke su hash-ovane sa bcrypt
- Sesije su sigurne
- CSRF zaštita
- Validacija korisničkih unosa

## Produkcija

Za produkciju preporučuje se:

1. Promena SECRET_KEY u app.py
2. Korišćenje PostgreSQL ili MySQL umesto SQLite
3. Dodavanje HTTPS
4. Korišćenie WSGI servera (Gunicorn, uWSGI)
5. Konfiguracija nginx-a za statičke fajlove

## Licenca

MIT License - možete slobodno koristiti i menjati kod.