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