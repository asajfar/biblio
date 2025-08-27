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
python simple_app.py
```

Aplikacija će biti dostupna na `http://localhost:5000`

**Napomena:** Ako imate problema sa kompatibilnošću paketa, koristite:
```bash
pip install --break-system-packages Flask bcrypt
python simple_app.py
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

### 7. Deljenje sa gostima
- Email integracija za slanje linkova
- WhatsApp deljenje
- Kopiranje linka u clipboard
- QR kod generisanje (opciono)

## Struktura aplikacije

```
svecani-prijemi/
├── simple_app.py          # Glavna Flask aplikacija
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
│   ├── seating_chart.html
│   ├── public_seating_chart.html  # Javna stranica za goste
│   ├── public_not_found.html      # Greška stranica
│   └── public_link.html           # Upravljanje javnim linkom
└── static/              # Statički fajlovi
    ├── css/
    │   └── style.css
    └── js/
        └── script.js
```

## Database modeli

- **users**: Korisnici aplikacije (id, username, email, password_hash)
- **receptions**: Svečani prijemi (id, name, date, venue, description, user_id, public_id, is_public)
- **tables**: Stolovi u sali (id, number, capacity, reception_id)
- **guests**: Gosti na prijemu (id, name, phone, email, notes, reception_id, table_id)

## Tehnologije

- **Backend**: Python Flask
- **Database**: SQLite (direktni pristup)
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Security**: bcrypt za hash-ovanje lozinki

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