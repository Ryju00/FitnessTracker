# Fitness Tracker

Kompleksowa aplikacja webowa do śledzenia aktywności fizycznej, treningów i żywienia. System umożliwia użytkownikom monitorowanie postępów treningowych, zarządzanie dietą oraz analizę wyników poprzez zaawansowane wykresy i statystyki.

## Spis treści

- [Funkcjonalności](#-funkcjonalności)
- [Technologie](#-technologie)
- [Wymagania](#-wymagania)
- [Instalacja](#-instalacja)
- [Struktura projektu](#-struktura-projektu)
- [Użytkowanie](#-użytkowanie)
- [Panel administratora](#-panel-administratora)
- [Zrzuty ekranu](#-zrzuty-ekranu)
- [Licencja](#-licencja)

## Funkcjonalności

### Dla użytkowników:
-  **Rejestracja i logowanie** - Bezpieczny system autentykacji z weryfikacją emaila
-  **Śledzenie treningów** - Dodawanie i zarządzanie treningami siłowymi i cardio
-  **Monitorowanie żywienia** - Rejestrowanie posiłków z automatycznym liczeniem kalorii
-  **Statystyki i wykresy** - Wizualizacja postępów treningowych i kalorycznych
-  **Profil użytkownika** - Edycja danych osobowych (wiek, wzrost, waga, płeć)
-  **Historia aktywności** - Przeglądanie wszystkich treningów i posiłków
-  **Licznik kalorii** - Automatyczne obliczanie spalonych kalorii podczas treningów

### Dla administratorów:
-  **Zarządzanie użytkownikami** - Przeglądanie i usuwanie kont użytkowników
-  **Zaawansowane filtry** - Filtrowanie użytkowników po wieku, wzroście, wadze, płci, dacie rejestracji
-  **Dashboard administratora** - Statystyki systemowe (liczba użytkowników, treningów, posiłków)
-  **Wykresy aktywności** - Wizualizacja aktywności użytkowników w czasie
-  **Zarządzanie rolami** - System ról (admin/user) z kontrolą dostępu

##  Technologie

- **Backend:** PHP 7.4+
- **Baza danych:** MySQL/MariaDB
- **Frontend:** 
  - Bootstrap 5.3.3
  - Chart.js 4.x (wykresy)
  - JavaScript (ES6+)
- **Styl:** Custom CSS z gradientami i animacjami
- **Architektura:** MVC pattern, OOP

##  Wymagania

- PHP 7.4 lub nowszy
- MySQL 5.7+ lub MariaDB 10.3+
- Apache/Nginx z mod_rewrite
- Composer (opcjonalnie)
- XAMPP/WAMP/LAMP (dla lokalnego środowiska)

##  Instalacja

### 1. Konfiguracja bazy danych

Utwórz nową bazę danych MySQL:

```sql
CREATE DATABASE gym_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Zaimportuj strukturę bazy danych:

```bash
mysql -u root -p gym_tracker < database/schema.sql
```

### 2. Konfiguracja połączenia

Edytuj plik `includes/db.php` i dostosuj dane połączenia:

```php
private $host = 'localhost';
private $db_name = 'gym_tracker';
private $username = 'root';
private $password = '';
```

### 4. Uruchomienie aplikacji

Jeśli używasz XAMPP:
1. Skopiuj projekt do folderu `htdocs`
2. Uruchom Apache i MySQL w panelu XAMPP
3. Otwórz przeglądarkę: `http://localhost/fitnesstracker`

### 5. Pierwsze logowanie

**Konto administratora (domyślne):**
```
Email: admin@fitness.pl
Hasło: password (domyślnie)
```

Aby nadać rolę administratora istniejącemu użytkownikowi:

```sql
UPDATE users SET role = 'admin' WHERE email = 'twoj-email@example.com';
```

##  Struktura projektu

```
FitnessTracker/
├── activities/          # Strony aktywności (cardio, treningi)
├── admin/              # Panel administratora
│   ├── admin_dashboard.php
│   └── manage_users.php
├── api/                # Endpointy API
│   ├── activity.php
│   ├── charts.php
│   ├── nutrition.php
│   ├── weekly_stats.php
│   └── workouts.php
├── assets/             # Zasoby statyczne
│   ├── css/
│   ├── js/
│   └── images/
├── auth/               # Autentykacja
│   ├── login.php
│   ├── register.php
│   └── verify.php
├── database/           # Skrypty SQL
├── history/            # Historia aktywności
├── includes/           # Komponenty współdzielone
│   ├── auth.php       # Klasa autentykacji
│   ├── db.php         # Połączenie z bazą danych
│   └── verify.php     # Weryfikacja emaila
├── user/              # Panel użytkownika
│   └── dashboard.php
├── index.php          # Strona główna (login/rejestracja)
└── README.md
```

##  Użytkowanie

### Dodawanie treningu

1. Zaloguj się do systemu
2. Przejdź do Dashboard → "Dodaj Trening Siłowy" lub "Dodaj Cardio"
3. Wypełnij formularz (typ, czas trwania, notatki)
4. Kliknij "Dodaj Trening"

### Rejestrowanie posiłku

1. Na dashboardzie znajdź sekcję "Posiłki"
2. Wprowadź nazwę posiłku i kalorie
3. Kliknij "Dodaj Posiłek"

### Przeglądanie statystyk

Dashboard automatycznie wyświetla:
- Wykres aktywności treningowej
- Wykres kalorii (spożyte vs spalone)
- Statystyki tygodniowe
- Ostatnie treningi i posiłki

##  Panel administratora

Dostęp: `http://localhost/fitnesstracker/admin/admin_dashboard.php`

### Funkcje:
- **Statystyki globalne:**
  - Liczba użytkowników
  - Łączna liczba treningów
  - Łączna liczba posiłków
  - Suma spalonych/spożytych kalorii
  
- **Zarządzanie użytkownikami:**
  - Filtrowanie po: email, data rejestracji, rola, wiek, wzrost, waga, płeć
  - Usuwanie użytkowników (wraz z ich danymi)
  - Przeglądanie profili użytkowników

- **Wykresy:**
  - Aktywność użytkowników w czasie (treningi/dzień)

## 🎨 Zrzuty ekranu

### Logowanie/Rejestracja
<img width="1003" height="736" alt="Zrzut ekranu 2026-01-05 220231" src="https://github.com/user-attachments/assets/20c61d58-4d32-4baa-b60c-8f19ce4d0ff0" />

### Formularz dla nowych użytkowników
<img width="817" height="870" alt="Zrzut ekranu 2026-01-05 213640" src="https://github.com/user-attachments/assets/b34e9238-97d2-4481-be18-90ed884c35ff" />

### Główny Dashboard
<img width="1426" height="1277" alt="Zrzut ekranu 2026-01-05 214134" src="https://github.com/user-attachments/assets/d88a8c54-ef83-42ac-b651-2dc40d77522e" />
<img width="1431" height="925" alt="Zrzut ekranu 2026-01-05 213953" src="https://github.com/user-attachments/assets/8977cecb-310e-4f5c-b011-130916871ea4" />

### Dashboard - Edycja Profilu
<img width="1343" height="1052" alt="Zrzut ekranu 2026-01-05 214244" src="https://github.com/user-attachments/assets/d0c4e5f6-2f96-4316-8fb5-fa00eec676e6" />

### Dashboard - Dodawanie Cardio
<img width="1335" height="757" alt="Zrzut ekranu 2026-01-05 214315" src="https://github.com/user-attachments/assets/61006d92-bacf-45e4-ae41-406f242b6832" />

### Dashboard - Dodawanie Treningu
<img width="1330" height="1013" alt="Zrzut ekranu 2026-01-05 214355" src="https://github.com/user-attachments/assets/60fe163f-04ad-43fb-9a33-e624021a14ed" />

### Dashboard - Historia Treningów
<img width="1340" height="480" alt="Zrzut ekranu 2026-01-05 214410" src="https://github.com/user-attachments/assets/d7b75ae8-0339-4514-9307-54f959e576d5" />

### Dashboard - Historia Posiłków
<img width="1329" height="428" alt="Zrzut ekranu 2026-01-05 214426" src="https://github.com/user-attachments/assets/98d10889-ad5a-45e1-9334-ecf6ccb901c3" />

### Panel administratora
<img width="1475" height="1205" alt="Zrzut ekranu 2026-01-05 220005" src="https://github.com/user-attachments/assets/d27ac6f0-1f52-41dc-8ae7-5f73a48d1775" />

### Zarządzanie użytkownikami
<img width="1387" height="1270" alt="Zrzut ekranu 2026-01-05 220214" src="https://github.com/user-attachments/assets/58e6a8b6-2319-4423-8f71-7a8db32d226e" />

##  Bezpieczeństwo

- Hashowanie haseł (bcrypt)
- Ochrona przed SQL injection (prepared statements)
- Weryfikacja danych wejściowych
- Kontrola sesji
- Weryfikacja emaila
- Zabezpieczenie przed CSRF (token w formularzach)

##  Kontakt

W przypadku pytań lub sugestii, otwórz issue na GitHubie lub skontaktuj się:

- GitHub: [@Ryju00](https://github.com/Ryju00)





