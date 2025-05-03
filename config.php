<?php
// config.php

// Definišemo parametre baze podataka:
// $host      - adresa servera baze podataka (obično 'localhost')
// $db_name   - ime baze podataka koju koristimo
// $user      - korisničko ime za pristup bazi
// $password  - lozinka za pristup bazi
$host      = 'localhost';
$db_name   = 'ombudsma_biblioteka';
$user      = 'ombudsma_biblioteka';
$password  = 'ApvOmbDtd';

// Pokušavamo da se povežemo na bazu koristeći funkciju mysqli_connect.
// Ova funkcija vraća konekciju (resource) ili FALSE ukoliko se konekcija ne uspostavi.
$conn = mysqli_connect($host, $user, $password, $db_name);

// Proveravamo da li je konekcija uspešno ostvarena.
// Ako varijabla $conn ima vrednost FALSE, to znači da nije uspelo povezivanje.
if (!$conn) {
    // Ako dođe do greške, ispisujemo poruku o grešci i zaustavljamo izvršavanje skripte.
    die("Greška prilikom povezivanja sa bazom: " . mysqli_connect_error());
}

// Postavljamo charset na 'utf8mb4' kako bismo osigurali pravilno kodiranje svih karaktera.
// Ovo je važno radi podrške za posebne karaktere, emodžije i sl.
mysqli_set_charset($conn, 'utf8mb4');

// Nakon uspešne konekcije, objekat / resurs $conn se koristi za sve naredne MySQL upite.
// Uključite ovaj fajl u ostale skripte pomoću require ili include naredbe, 
// kako biste mogli da koristite $conn u njima.

// Na primer, u drugom fajlu možete ga koristiti ovako:
// require_once 'config.php';
// $query = "SELECT * FROM korisnik";
// $result = mysqli_query($conn, $query);

// Kraj fajla.
?>
