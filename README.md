/nails-booking/
│
├── /public/
│   ├── index.html                  <-- Galvenā rezervācijas lapa (ar kalendāru)
│   ├── account.html                <-- Lietotāja konts un rezervāciju skats
│   ├── style.css                   <-- Frontend dizains
│   ├── booking.js                  <-- Rezervācijas loģika + kalendārs
│   └── account.js                  <-- Lietotāja autentifikācija un rezervācijas
│
├── /api/
│   ├── get-availability.php        <-- Brīvie laiki pēc datuma
│   ├── submit-anonymous-booking.php<-- Anonīma rezervācija
│   ├── register.php                <-- Lietotāja reģistrācija
│   ├── login.php                   <-- Lietotāja ieeja
│   ├── get-user-bookings.php       <-- Lietotāja rezervācijas
│   ├── update-booking.php          <-- Rediģēt rezervāciju
│   ├── add-client.php              <-- Pievieno klientu admin panelī
│   └── delete-booking.php          <-- Dzēst rezervāciju
│
├── /admin/
│   ├── dashboard.html
│   ├── bookings.php                <-- Rezervācijas ar rediģēšanu
│   ├── edit-booking.php            <-- Rezervācijas labošana
│   ├── add-client.php              <-- Manuāls klienta pievienošana
│   ├── services.php                <-- Pakalpojumu pārvaldība
│   ├── edit-service.php            <-- Pakalpojuma labošana
│   ├── login.php                   <-- Admin ieeja
│   ├── logout.php                  <-- Iziet no admina
│   └── actions.php                 <-- Pievienot/dzēst/labot pakalpojumus
│   └── style.css                   <-- Atsevišķs stils admin panelim
│
├── /core/
│   ├── db.php                      <-- Datubāzes pieslēgums
│   └── auth.php                    <-- Autentifikācijas funkcijas
│
└── /sql/
    └── setup.sql                   <-- Datubāzes struktūra





    
    nails-booking/
│
├── public/                  <-- Frontend (lietotāja puse)
│   ├── index.html           <-- Sākumlapa ar kalendāru -->
│   ├── account.html         <-- Lietotāja konts + reģistrācija -->
│   ├── booking.js           <-- Rezervācijas logika -->
│   ├── account.js           <-- Autentifikācijas loģika -->
│   └── style.css            <-- Frontend dizains -->

├── admin/                   <-- Admin panelis -->
│   ├── dashboard.html       <-- Galvenā admin lapa -->
│   ├── bookings.php         <-- Rezervāciju saraksts -->
│   ├── edit-booking.php     <-- Rediģē rezervāciju -->
│   ├── services.php         <-- Pakalpojumu pārvaldība -->
│   ├── edit-service.php     <-- Rediģē pakalpojumus -->
│   ├── login.php            <-- Admin ieeja -->
│   ├── logout.php           <-- Iziet no admina -->
│   ├── calendar-view.php    <-- Brīvie laiki ar vizuālo skatu -->
│   ├── add-client.php       <-- Manuāls klienta pievienošana -->
│   ├── actions.php          <-- Pievieno / dzēš / labo pakalpojumus -->
│   └── style.css            <-- Atsevišķais stils admin panelim -->

├── api/                     <-- Backend API punkti -->
│   ├── get-availability.php <-- Brīvie laiki pēc datuma -->
│   ├── submit-anonymous-booking.php <-- Anonīma rezervācija -->
│   ├── register.php         <-- Lietotāja reģistrācija -->
│   ├── login.php            <-- Lietotāja autentifikācija -->
│   ├── get-user-bookings.php <-- Ielādē lietotāja rezervācijas -->
│   ├── update-booking.php   <-- Labo esošu rezervāciju -->
│   ├── delete-booking.php   <-- Dzēš rezervāciju -->
│   ├── add-client.php       <-- Pievieno klientu no admina -->
│   └── submit-booking.php   <-- Saglabā rezervāciju ar lietotāja ID -->

├── core/                    <-- Datubāzes pieslēgums un autentifikācija -->
│   ├── db.php               <-- PDO datubāzes pieslēgums -->
│   └── auth.php             <-- Klients/admin autentifikācijas funkcijas -->

└── sql/
    └── setup.sql            <-- Datubāzes struktūra


    --------------------------------------------------------------
    nails-booking/
├── public/
│   ├── index.html
│   ├── booking.js
│   ├── account.js
│   ├── style.css
│   └── uploads/  # Attēlu mape
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   ├── bookings/
│   │   ├── get-availability.php
│   │   ├── submit-anonymous-booking.php
│   │   ├── submit-booking.php
│   │   ├── get-user-bookings.php
│   │   ├── update-booking.php
│   │   └── delete-booking.php
│   ├── admin/
│   │   ├── get-services.php
│   │   ├── manage-services.php
│   │   ├── manage-hours.php
│   │   └── manage-bookings.php
│   └── core/
│       ├── db.php
│       ├── auth.php
│       └── functions.php
├── admin/
│   ├── dashboard.html
│   ├── bookings.php
│   ├── edit-booking.php
│   ├── services.php
│   ├── edit-service.php
│   ├── calendar-view.php
│   ├── login.php
│   ├── logout.php
│   └── style.css
└── sql/
    └── setup.sql



    -------------------------
    /Applications/XAMPP/htdocs/kursa-darbi/nails-booking/
├── .htaccess                       // Servera konfigurācija (atjaunināts)
├── api/
│   ├── auth/
│   │   ├── register.php            // Lietotāju reģistrācija
│   │   ├── login.php               // Lietotāju pieteikšanās
│   │   ├── logout.php              // Lietotāju izrakstīšanās
│   │   └── check-role.php          // Lomas pārbaude
│   ├── bookings/
│   │   ├── get-services.php        // Pakalpojumu ielāde (atjaunināts)
│   │   ├── get-availability.php    // Pieejamo laiku ielāde
│   │   ├── submit-anonymous-booking.php // Anonīmo rezervāciju API
│   │   ├── submit-booking.php      // Reģistrēto lietotāju rezervācijas
│   │   ├── update-booking.php      // Rezervāciju atjaunināšana
│   │   ├── delete-booking.php      // Rezervāciju dzēšana
│   │   └── get-user-bookings.php   // Lietotāja rezervāciju ielāde
│   └── admin/
|        |-get-stats.php - Jaunais fails statistikai
│       ├── manage-services.php     // Pakalpojumu pārvaldība
│       ├── manage-hours.php        // Darba laiku pārvaldība
│       ├── manage-bookings.php     // Rezervāciju pārvaldība
├── core/
│   ├── db.php                      // Datubāzes savienojums (atjaunināts)
│   ├── functions.php               // Kopīgās funkcijas
│   └── auth.php                    // Admina autentifikācija
├── public/
│   ├── index.html                  // Galvenā lapa
│   ├── booking.js                  // Rezervāciju funkcionalitāte (atjaunināts)
│   ├── account.js                  // Lietotāju autentifikācija
│   ├── style.css                   // Stili
│   └── uploads/                    // Attēlu augšupielāde
├── admin/
│   ├── dashboard.html              // Admina panelis
│   ├── bookings.php                // Rezervāciju pārvaldība
│   ├── edit-booking.php            // Rezervācijas labošana
│   ├── add-client.php              // Klienta pievienošana
│   ├── services.php                // Pakalpojumu pārvaldība
│   ├── edit-service.php            // Pakalpojuma labošana
│   ├── calendar-view.php           // Darba laiku pārvaldība
│   ├── login.php                   // Admina pieteikšanās
│   ├── logout.php                  // Admina izrakstīšanās
│   ├── actions.php                 // Pakalpojumu/laiku CRUD
│   ├── admin.js                    // Admina funkcionalitāte
│   └── style.css                   // Admina stili
├── sql/
│   └── setup.sql                   // Datubāzes inicializācija (jauns)
└── test-db.php                     // Datubāzes testa skripts


------------------
-- /nails-booking/sql/update_database.sql
-- Salīdzinājums un atjaunināšana starp esošo un jauno struktūru

-- ====================
-- GALVENĀS ATŠĶIRĪBAS:
-- ====================

-- 1. ADMINS tabula - pietrūkst kolonnām
-- 2. SERVICES tabula - pietrūkst kolonnu
-- 3. BOOKINGS tabula - pietrūkst kolonnu
-- 4. USERS tabula - pietrūkst kolonnu
-- 5. Trūkst demo datu

USE nail_studio;

-- ===========================================
-- 1. ATJAUNINA ADMINS TABULU
-- ===========================================

-- Pievieno trūkstošās kolonnas admins tabulai
ALTER TABLE `admins` 
ADD COLUMN IF NOT EXISTS `name` VARCHAR(100) DEFAULT NULL AFTER `password_hash`,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `token`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Pievieno indeksus, ja nav
ALTER TABLE `admins` 
ADD UNIQUE KEY IF NOT EXISTS `idx_email` (`email`),
ADD UNIQUE KEY IF NOT EXISTS `idx_token` (`token`);

-- ===========================================
-- 2. ATJAUNINA SERVICES TABULU
-- ===========================================

-- Pievieno trūkstošās kolonnas services tabulai
ALTER TABLE `services` 
ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `duration`,
ADD COLUMN IF NOT EXISTS `active` TINYINT(1) DEFAULT 1 AFTER `description`,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `active`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ===========================================
-- 3. ATJAUNINA BOOKINGS TABULU
-- ===========================================

-- Pievieno trūkstošās kolonnas bookings tabulai
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending' AFTER `image`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ===========================================
-- 4. ATJAUNINA USERS TABULU
-- ===========================================

-- Pievieno trūkstošās kolonnas users tabulai (jau ir created_at)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ===========================================
-- 5. PIEVIENO DEMO ADMIN LIETOTĀJU
-- ===========================================

-- Izveido demo admin (parole: admin123)
INSERT IGNORE INTO `admins` (`username`, `email`, `password_hash`, `name`) 
VALUES (
    'admin@gmail.com', 
    'admin@gmail.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Galvenais administrators'
);

-- ===========================================
-- 6. PIEVIENO DEMO PAKALPOJUMUS
-- ===========================================

INSERT IGNORE INTO `services` (`name`, `price`, `duration`, `description`, `active`) VALUES
('Manikīrs', 25.00, 60, 'Klasiskais manikīrs ar nagu veidošanu', 1),
('Pedikīrs', 30.00, 75, 'Pilns pedikīrs ar pēdu aprūpi', 1),
('Gel laka', 20.00, 45, 'Gel lakas uzklāšana nagiem', 1),
('Nagu dizains', 35.00, 90, 'Māksliniecisks nagu dizains', 1),
('Nagu stiprināšana', 40.00, 120, 'Nagu stiprināšana ar īpašiem līdzekļiem', 1),
('Spa manikīrs', 45.00, 90, 'Luksusa manikīrs ar spa procedūrām', 1),
('Nagu pagarināšana', 50.00, 150, 'Akrīla vai gel nagu pagarināšana', 1),
('Franzuski manikīrs', 28.00, 70, 'Elegants klasisks franzusku stils', 1);

-- ===========================================
-- 7. PIEVIENO DEMO DARBA LAIKUS
-- ===========================================

INSERT IGNORE INTO `working_hours` (`date`, `start_time`, `end_time`, `is_available`) VALUES
(CURDATE(), '09:00:00', '18:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '16:00:00', 0),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '18:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:00:00', '18:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', '17:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 6 DAY), '10:00:00', '15:00:00', 1),
(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', '18:00:00', 1);

-- ===========================================
-- 8. PIEVIENO DEMO REZERVĀCIJAS
-- ===========================================

INSERT IGNORE INTO `bookings` (`name`, `phone`, `email`, `service`, `date`, `time`, `status`, `comment`) VALUES
('Anna Kalniņa', '+371 12345678', 'anna@example.com', 'Manikīrs', CURDATE(), '10:00:00', 'confirmed', 'Vēlas sarkanu krāsu'),
('Līga Bērziņa', '+371 87654321', 'liga@example.com', 'Pedikīrs', CURDATE(), '14:30:00', 'pending', 'Pirmo reizi'),
('Māra Ozola', '+371 11111111', 'mara@example.com', 'Gel laka', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'confirmed', 'Vēlas dizainu'),
('Ieva Liepa', '+371 22222222', 'ieva@example.com', 'Nagu dizains', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00:00', 'pending', 'Kāzu nagi'),
('Laura Krūmiņa', '+371 33333333', 'laura@example.com', 'Spa manikīrs', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 'confirmed', '');

-- ===========================================
-- 9. PIEVIENO DEMO LIETOTĀJUS
-- ===========================================

-- Demo lietotājs (parole: test123)
INSERT IGNORE INTO `users` (`name`, `phone`, `email`, `password_hash`) VALUES
('Testētāja', '+371 98765432', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ===========================================
-- 10. PĀRBAUDES UN UZLABOJUMI
-- ===========================================

-- Notīra vecus token (drošībai)
UPDATE `admins` SET `token` = NULL;
UPDATE `users` SET `token` = NULL;

-- Pārbauda Foreign Key constraints
ALTER TABLE `bookings` DROP FOREIGN KEY IF EXISTS `bookings_ibfk_1`;
ALTER TABLE `bookings` 
ADD CONSTRAINT `fk_bookings_user` 
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ===========================================
-- REZULTĀTU PĀRBAUDE
-- ===========================================

-- Parāda tabulu struktūras
SELECT 'ADMINS TABULA:' as info;
DESCRIBE admins;

SELECT 'SERVICES TABULA:' as info;
DESCRIBE services;

SELECT 'BOOKINGS TABULA:' as info;
DESCRIBE bookings;

SELECT 'USERS TABULA:' as info;
DESCRIBE users;

SELECT 'WORKING_HOURS TABULA:' as info;
DESCRIBE working_hours;

-- Parāda ierakstu skaitu
SELECT 
    'DATU STATISTIKA:' as info,
    (SELECT COUNT(*) FROM admins) as admins_count,
    (SELECT COUNT(*) FROM services) as services_count,
    (SELECT COUNT(*) FROM bookings) as bookings_count,
    (SELECT COUNT(*) FROM users) as users_count,
    (SELECT COUNT(*) FROM working_hours) as working_hours_count;

SELECT '✅ Datubāze veiksmīgi atjaunināta ar visām trūkstošajām kolonnām un demo datiem!' as status;