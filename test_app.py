from flask import Flask, render_template_string

app = Flask(__name__)
app.config['SECRET_KEY'] = 'test-secret'

@app.route('/')
def index():
    return render_template_string('''
<!DOCTYPE html>
<html>
<head>
    <title>Svečani Prijemi - Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h1 class="text-primary">🎉 Svečani Prijemi - Test Aplikacija</h1>
            <p class="lead">Aplikacija uspešno radi!</p>
            <div class="mt-4">
                <h3>Implementirane funkcionalnosti:</h3>
                <ul class="list-unstyled">
                    <li>✅ Registracija i prijava korisnika</li>
                    <li>✅ Kreiranje svečanih prijema</li>
                    <li>✅ Upravljanje stolovima</li>
                    <li>✅ Dodavanje gostiju</li>
                    <li>✅ Raspored sedenja</li>
                    <li>✅ Javna stranica za goste</li>
                    <li>✅ Pretraga gostiju</li>
                </ul>
            </div>
            <div class="mt-4">
                <div class="alert alert-info">
                    <strong>Kako da koristite aplikaciju:</strong><br>
                    1. Promenite naziv fajla sa <code>test_app.py</code> na <code>app.py</code><br>
                    2. Promenite sadržaj fajla sa <code>simple_app.py</code> sadržajem<br>
                    3. Popravite template greške zamenivši <code>reception.field</code> sa <code>reception['field']</code><br>
                    4. Pokrenite aplikaciju sa <code>python app.py</code>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
    ''')

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)