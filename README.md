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

### 1. Sklonuj repozytorium

```bash
git clone https://github.com/twoje-konto/fitness-tracker.git
cd fitness-tracker
```

### 2. Konfiguracja bazy danych

Utwórz nową bazę danych MySQL:

```sql
CREATE DATABASE fitness_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Zaimportuj strukturę bazy danych:

```bash
mysql -u root -p fitness_tracker < database/schema.sql
```

### 3. Konfiguracja połączenia

Edytuj plik `includes/db.php` i dostosuj dane połączenia:

```php
private $host = 'localhost';
private $db_name = 'fitness_tracker';
private $username = 'root';
private $password = '';
```

### 4. Uruchomienie aplikacji

Jeśli używasz XAMPP:
1. Skopiuj projekt do folderu `htdocs`
2. Uruchom Apache i MySQL w panelu XAMPP
3. Otwórz przeglądarkę: `http://localhost/fitness-tracker`

### 5. Pierwsze logowanie

**Konto administratora (domyślne):**
```
Email: admin@example.com
Hasło: [ustaw podczas pierwszej rejestracji]
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

Dostęp: `http://localhost/fitness-tracker/admin/admin_dashboard.php`

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

### Dashboard użytkownika
![Dashboard](docs/screenshots/dashboard.png)

### Panel administratora
![Admin Panel](docs/screenshots/admin.png)

### Zarządzanie użytkownikami
![Manage Users](docs/screenshots/manage-users.png)

##  Bezpieczeństwo

- Hashowanie haseł (bcrypt)
- Ochrona przed SQL injection (prepared statements)
- Weryfikacja danych wejściowych
- Kontrola sesji
- Weryfikacja emaila
- Zabezpieczenie przed CSRF (token w formularzach)

##  Licencja

Ten projekt jest dostępny na licencji MIT. Zobacz plik [LICENSE](LICENSE) po szczegóły.

##  Kontakt

W przypadku pytań lub sugestii, otwórz issue na GitHubie lub skontaktuj się:

- GitHub: [@twoje-konto](https://github.com/twoje-konto)
- Email: twoj-email@example.com

