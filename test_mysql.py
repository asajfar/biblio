#!/usr/bin/env python3
"""
Test script za MySQL konekciju
Koristi se za testiranje da li je MySQL ispravno podešen
"""

import mysql.connector
from mysql.connector import Error
import os

# MySQL konfiguracija
MYSQL_CONFIG = {
    'host': os.environ.get('MYSQL_HOST', 'localhost'),
    'port': int(os.environ.get('MYSQL_PORT', 3306)),
    'user': os.environ.get('MYSQL_USER', 'svecani_prijemi'),
    'password': os.environ.get('MYSQL_PASSWORD', 'password123'),
    'database': os.environ.get('MYSQL_DATABASE', 'svecani_prijemi'),
}

def test_mysql_connection():
    """Test MySQL konekcije"""
    print("🔄 Testiranje MySQL konekcije...")
    print(f"Host: {MYSQL_CONFIG['host']}:{MYSQL_CONFIG['port']}")
    print(f"Database: {MYSQL_CONFIG['database']}")
    print(f"User: {MYSQL_CONFIG['user']}")
    print("-" * 50)
    
    try:
        # Pokušaj konekciju
        connection = mysql.connector.connect(**MYSQL_CONFIG)
        
        if connection.is_connected():
            print("✅ Uspešno povezano sa MySQL bazom!")
            
            # Informacije o konekciji
            db_info = connection.get_server_info()
            print(f"📊 MySQL Server verzija: {db_info}")
            
            cursor = connection.cursor()
            
            # Proveri trenutnu bazu
            cursor.execute("SELECT DATABASE();")
            database_name = cursor.fetchone()
            print(f"🗄️  Trenutna baza: {database_name[0]}")
            
            # Prikaži tabele
            cursor.execute("SHOW TABLES;")
            tables = cursor.fetchall()
            
            if tables:
                print(f"📋 Tabele u bazi ({len(tables)}):")
                for table in tables:
                    print(f"   - {table[0]}")
                    
                # Proveri broj zapisa u tabelama
                print("\n📈 Broj zapisa u tabelama:")
                for table in tables:
                    cursor.execute(f"SELECT COUNT(*) FROM {table[0]};")
                    count = cursor.fetchone()[0]
                    print(f"   - {table[0]}: {count} zapisa")
            else:
                print("📋 Nema tabela - aplikacija će ih kreirati automatski")
            
            # Test basic SQL operacija
            print("\n🧪 Test osnovnih SQL operacija...")
            cursor.execute("SELECT 1 as test_column;")
            result = cursor.fetchone()
            if result[0] == 1:
                print("✅ SQL query test prošao uspešno")
            
            cursor.close()
            
    except Error as e:
        print(f"❌ Greška pri konekciji sa MySQL: {e}")
        print("\n🔧 Mogući uzroci:")
        print("   1. MySQL server nije pokrenut")
        print("   2. Pogrešni podaci za konekciju")
        print("   3. Korisnik nema dozvole")
        print("   4. Baza ne postoji")
        print("\n💡 Rešenja:")
        print("   - Pokrenite: docker-compose up -d")
        print("   - Ili pokrenite: sudo systemctl start mysql")
        print("   - Proverite .env fajl")
        print("   - Pokrenite mysql_setup.sql script")
        return False
        
    except Exception as e:
        print(f"❌ Neočekivana greška: {e}")
        return False
        
    finally:
        if 'connection' in locals() and connection.is_connected():
            connection.close()
            print("🔐 MySQL konekcija zatvorena")
    
    return True

def test_environment():
    """Test environment varijabli"""
    print("\n🌍 Environment varijable:")
    env_vars = ['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_DATABASE']
    
    for var in env_vars:
        value = os.environ.get(var, f"DEFAULT ({MYSQL_CONFIG[var.split('_')[1].lower()]})")
        # Ne prikazuj lozinku u potpunosti
        if 'PASSWORD' in var and value != 'DEFAULT':
            value = '*' * len(value)
        print(f"   {var}: {value}")

def main():
    """Glavna funkcija"""
    print("=" * 60)
    print("🧪 MYSQL TEST SCRIPT - Svečani Prijemi")
    print("=" * 60)
    
    test_environment()
    
    success = test_mysql_connection()
    
    print("\n" + "=" * 60)
    if success:
        print("🎉 Svi testovi su prošli uspešno!")
        print("🚀 Možete pokrenuti aplikaciju: python mysql_app.py")
    else:
        print("⚠️  Postoje problemi sa MySQL konfiguraciom")
        print("📖 Pogledajte README.md za instrukcije")
    print("=" * 60)

if __name__ == "__main__":
    main()