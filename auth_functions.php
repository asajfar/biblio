<?php
/*
    auth_functions.php

    Ovaj fajl sadrži funkcije za:
    - Registraciju korisnika
    - Prijavu korisnika
    - Slanje zahteva za reset lozinke (generisanje reset tokena)
    - Resetovanje lozinke
    - Odjavu korisnika

    Napomene:
    • Lozinke se enkriptuju pomoću MD5 (prema specifikaciji – MD5 nije najsigurniji metod u produkciji).
    • Email verifikacioni token važi 3 dana, dok reset token važi 1 dan.
    • Koristi se “remember me” kolačić koji čuva korisnika ulogovanim 10 dana.
    • Ovaj fajl očekuje da postoji konekcija sa bazom u varijabli $conn,
      koju smo definisali u config.php koristeći MySQLi.
*/

/*
    Funkcija registerUser()
    Parametri:
      - $conn      : MySQLi konekcija
      - $ime       : Ime korisnika
      - $prezime   : Prezime korisnika
      - $email     : Email adresa korisnika
      - $password  : Lozinka
      - $telefon   : (Opcionalno) Broj telefona korisnika

    Funkcija vrši proveru da li email već postoji, zahash-uje lozinku pomoću MD5,
    generiše jedinstveni verifikacioni token i datum isteka (trenutno vreme + 3 dana),
    i upisuje novog korisnika u tabelu KORISNIK.
*/
function registerUser($conn, $ime, $prezime, $email, $password, $telefon = null) {
    // Uklanjamo eventualne praznine sa početka i kraja unetih vrednosti
    $ime = trim($ime);
    $prezime = trim($prezime);
    $email = trim($email);
    $password = trim($password);

    // Pripremamo SQL upit da proverimo da li već postoji korisnik sa unetim email-om
    $query = "SELECT id FROM KORISNIK WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Ako priprema upita ne uspe, zaustavljamo izvršavanje i ispisujemo grešku
        die("Greška u pripremi SQL upita: " . mysqli_error($conn));
    }
    // Povezujemo varijablu $email sa placeholder-om u SQL upitu
    mysqli_stmt_bind_param($stmt, "s", $email);
    // Izvršavamo upit
    mysqli_stmt_execute($stmt);
    // Čuvamo rezultat upita da bismo mogli da proverimo broj redova
    mysqli_stmt_store_result($stmt);

    // Ako je broj redova veći od 0, email već postoji u bazi
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        return "Email adresa već postoji u sistemu.";
    }
    // Zatvaramo prethodno pripremljeni statement
    mysqli_stmt_close($stmt);

    // Hash-ujemo lozinku koristeći MD5 (prema specifikaciji)
    $hashed_password = md5($password);

    // Generišemo jedinstveni verifikacioni token za potvrdu naloga
    $token = md5(uniqid(mt_rand(), true));

    // Postavljamo datum isteka tokena na 3 dana od trenutka registracije
    $verifikacija_expires = date("Y-m-d H:i:s", strtotime('+3 days'));

    // Pripremamo SQL upit za unos novog korisnika
    $insertQuery = "INSERT INTO KORISNIK (ime, prezime, email, lozinka, telefon, verifikacioni_token, verifikovan, verifikacija_expires)
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);
    if (!$stmt) {
        die("Greška u pripremi SQL upita za unos korisnika: " . mysqli_error($conn));
    }
    // Povezujemo parametre sa SQL upitom: svi parametri su stringovi (s)
    mysqli_stmt_bind_param($stmt, "sssssss", $ime, $prezime, $email, $hashed_password, $telefon, $token, $verifikacija_expires);

    // Izvršavamo upit i proveravamo da li je unos uspešan
    if (!mysqli_stmt_execute($stmt)) {
        die("Greška prilikom registracije korisnika: " . mysqli_stmt_error($stmt));
    }
    // Možemo dobiti ID novo unetog korisnika (nije obavezno)
    $newUserId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // U stvarnoj primeni, ovde bi trebalo poslati verifikacioni email korisniku.
    // Primer verifikacionog linka (ovde koristite vlastitu domenu i skriptu, npr. verify.php):
    // $verificationLink = "https://yourdomain.com/verify.php?token=" . $token;
    // mail($email, "Verifikacija naloga", "Kliknite na link za verifikaciju: $verificationLink");

    // Vraćamo poruku o uspešnoj registraciji sa napomenom o verifikaciji
    return "Registracija je uspešna. Proverite email za verifikaciju naloga.";
}

/*
    Funkcija loginUser()
    Parametri:
      - $conn     : MySQLi konekcija
      - $email    : Email adresa korisnika koji se prijavljuje
      - $password : Uneta lozinka korisnika

    Funkcija proverava da li postoji korisnik sa unetim email-om i md5 hešom lozinke.
    Takođe proverava da li je nalog verifikovan.
    Ako su uslovi ispunjeni, pokreće se sesija i postavljaju se sesijske promenljive.
    Implementira se i "remember me" mehanizam kroz kolačić koji važi 10 dana.
*/
function loginUser($conn, $email, $password) {
    // Uklanjamo praznine sa vrednosti
    $email = trim($email);
    $password = trim($password);
    // Hash-ujemo lozinku koristeći MD5
    $hashed_password = md5($password);

    // Pripremamo SQL upit da pronađemo korisnika sa datim email-om i hešovanom lozinkom
    $query = "SELECT id, ime, prezime, verifikovan FROM KORISNIK WHERE email = ? AND lozinka = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Greška u pripremi SQL upita: " . mysqli_error($conn));
    }
    // Povezujemo vrednosti email i lozinke
    mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);
    mysqli_stmt_execute($stmt);
    // Vezujemo rezultate upita sa promenljivama
    mysqli_stmt_bind_result($stmt, $user_id, $ime, $prezime, $verifikovan);

    // Ako se pronađe korisnik, preuzimamo podatke
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        // Proveravamo da li je korisnik verifikovan (verifikovan = 1 znači da jeste)
        if($verifikovan == 0) {
            return "Molimo, verifikujte svoj nalog pre prijave.";
        }
        // Pokrećemo sesiju (ako već nije pokrenuta)
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Čuvamo podatke o korisniku u sesiji
        $_SESSION['user_id'] = $user_id;
        $_SESSION['ime']     = $ime;
        $_SESSION['prezime'] = $prezime;
        $_SESSION['email']   = $email;
        // Postavljamo "remember me" kolačić koji traje 10 dana (10 dana * 24 sata * 60 minuta * 60 sekundi)
        setcookie("user_id", $user_id, time() + (10 * 24 * 60 * 60), "/");
        return "Prijava je uspešna.";
    } else {
        // Ako korisnik nije pronađen, vraćamo poruku o grešci
        mysqli_stmt_close($stmt);
        return "Neispravni email ili lozinka.";
    }
}

/*
    Funkcija forgotPasswordRequest()
    Parametar:
      - $conn  : MySQLi konekcija
      - $email : Email adresa korisnika koji želi da resetuje lozinku

    Funkcija proverava da li postoji korisnik sa unetim email-om.
    Ako postoji, generiše se reset token i postavlja datum isteka (trenutno vreme + 1 dan).
    Ažurira se korisnikov zapis u bazi sa generisanim tokenom i datumom isteka.
*/
function forgotPasswordRequest($conn, $email) {
    // Uklanjamo praznine iz email-a
    $email = trim($email);

    // Pripremamo upit da proverimo da li postoji korisnik sa unetim email-om
    $query = "SELECT id FROM KORISNIK WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Greška u pripremi SQL upita: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 0) {
        mysqli_stmt_close($stmt);
        return "Korisnik sa unetim emailom ne postoji.";
    }
    mysqli_stmt_close($stmt);

    // Generišemo reset token koristeći MD5 i jedinstveni identifikator
    $reset_token = md5(uniqid(mt_rand(), true));
    // Postavljamo datum isteka reset tokena na 1 dan od trenutka zahteva
    $reset_expires = date("Y-m-d H:i:s", strtotime('+1 day'));

    // Pripremamo upit za ažuriranje korisničkog zapisa sa reset tokenom i datumom isteka
    $updateQuery = "UPDATE KORISNIK SET reset_token = ?, reset_expires = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    if (!$stmt) {
        die("Greška u pripremi SQL upita za update reset tokena: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "sss", $reset_token, $reset_expires, $email);
    if (!mysqli_stmt_execute($stmt)) {
        die("Greška pri ažuriranju reset tokena: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // U stvarnom sistemu, ovde biste poslali email korisniku sa linkom za resetovanje.
    // Primer linka: 
    // $resetLink = "https://yourdomain.com/reset.php?token=" . $reset_token;
    // mail($email, "Reset lozinke", "Kliknite na sledeći link da resetujete lozinku: $resetLink");

    return "Email za reset lozinke je poslat, proverite svoj inbox.";
}

/*
    Funkcija resetPassword()
    Parametri:
      - $conn         : MySQLi konekcija
      - $reset_token  : Reset token koji je korisniku poslat putem emaila
      - $new_password : Nova lozinka koju korisnik želi da postavi

    Funkcija proverava da li postoji korisnik sa unetim reset tokenom, kao i da li token nije istekao.
    Ako je sve uredu, nova lozinka se hash-uje i ažurira u bazi, a reset token i datum isteka se brišu.
*/
function resetPassword($conn, $reset_token, $new_password) {
    // Uklanjamo praznine iz unetih vrednosti
    $reset_token = trim($reset_token);
    $new_password = trim($new_password);
    // Hash-ujemo novu lozinku
    $hashed_new_password = md5($new_password);

    // Pripremamo upit da pronađemo korisnika sa unetim reset tokenom
    $query = "SELECT id, reset_expires FROM KORISNIK WHERE reset_token = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Greška u pripremi SQL upita: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "s", $reset_token);
    mysqli_stmt_execute($stmt);
    // Vežemo rezultate u promenljive
    mysqli_stmt_bind_result($stmt, $user_id, $reset_expires);

    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        // Proveravamo da li je reset token istekao
        if (strtotime($reset_expires) < time()) {
            return "Reset token je istekao. Pokušajte ponovo.";
        }

        // Ako je token validan, ažuriramo lozinku i brišemo reset token i datum isteka
        $updateQuery = "UPDATE KORISNIK SET lozinka = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        if (!$stmt) {
            die("Greška u pripremi SQL upita za reset lozinke: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "si", $hashed_new_password, $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            die("Greška pri resetovanju lozinke: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return "Lozinka je uspešno resetovana.";
    } else {
        mysqli_stmt_close($stmt);
        return "Nevažeći reset token.";
    }
}

/*
    Funkcija logoutUser()
    Ova funkcija uništava sesiju, briše sve sesijske promenljive i kolačiće,
    čime korisnik bivaju odjavljeni iz sistema.
*/
function logoutUser() {
    // Pokrećemo sesiju ukoliko nije već pokrenuta
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Brišemo sve sesijske promenljive
    $_SESSION = array();
    
    // Ako se koristi kolačić za sesiju, brišemo ga tako što postavljamo prošlo vreme
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Uništavamo sesiju
    session_destroy();
    
    // Brišemo kolačić "remember me" ako postoji
    if (isset($_COOKIE['user_id'])) {
        setcookie("user_id", "", time() - 3600, "/");
    }
    
    return "Uspešno ste se odjavili.";
}
?>
