// Pievienot šo kodu account.js failā (aizstāj esošās showLogin un showRegister funkcijas)

// =================== UZLABOTĀS MODAL FUNKCIJAS ===================

// Funkcija modālo logu atvēršanai
function openModal() {
    document.body.classList.add('modal-open');
}

// Funkcija modālo logu aizvēršanai  
function closeModal() {
    document.body.classList.remove('modal-open');
}

// UZLABOTĀ showLogin funkcija
function showLogin() {
    hideAllViews();
    document.getElementById('login-form').classList.remove('hidden');
    openModal(); // Pievienojam modal-open klasi
}

// UZLABOTĀ showRegister funkcija
function showRegister() {
    hideAllViews();
    document.getElementById('register-form').classList.remove('hidden');
    openModal(); // Pievienojam modal-open klasi
}

// UZLABOTĀ hideAuthForms funkcija
function hideAuthForms() {
    closeModal(); // Noņemam modal-open klasi
    showCalendarView();
}

// Pievienojam event listener, lai aizvērtu modal, ja noklikšķina ārpus tā
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('auth-form')) {
        hideAuthForms();
    }
});

// ESC taustiņš arī aizvērs modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.auth-form:not(.hidden)');
        if (openModal) {
            hideAuthForms();
        }
    }
});

// Uzlabojam arī citas funkcijas, kas aizvēr modal
function logoutUser() {
    if (!confirm('🚪 Vai tiešām vēlies iziet?')) {
        return;
    }
    
    closeModal(); // Noņemam modal overlay
    
    const token = localStorage.getItem('auth_token');
    
    if (token) {
        fetch('/api/auth/logout.php', {
            method: 'POST',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            console.log('Logout response:', data);
            if (data.success) {
                localStorage.removeItem('auth_token');
                currentUser = null;
                userRole = 'guest';
                
                showCalendarView();
                alert('👋 Tu esi veiksmīgi izrakstījies! Vari turpināt veikt rezervācijas kā viesis vai pierakstīties atpakaļ.');
            } else {
                alert('❌ Kļūda izrakstīšanās laikā: ' + (data.error || 'Nezināma kļūda'));
            }
        })
        .catch(err => {
            console.error('Izrakstīšanās kļūda:', err);
            localStorage.removeItem('auth_token');
            currentUser = null;
            userRole = 'guest';
            showCalendarView();
            alert('⚠️ Izrakstīšanās ar kļūdu, bet lokālie dati notīrīti.');
        });
    }
    
    localStorage.removeItem('auth_token');
    currentUser = null;
    userRole = 'guest';
    
    showCalendarView();
}

// Uzlabojam registerUser funkciju
function registerUser() {
    const name = document.getElementById('reg-name')?.value.trim();
    const phone = document.getElementById('reg-phone')?.value.trim();
    const email = document.getElementById('reg-email')?.value.trim();
    const password = document.getElementById('reg-password')?.value.trim();

    // Validācija
    if (!name || !phone || !email || !password) {
        alert('❗ Lūdzu, aizpildi visus laukus.');
        return;
    }
    if (!/^\+?\d{8,}$/.test(phone)) {
        alert('❗ Telefonam jābūt vismaz 8 cipariem (ar vai bez + priekšā)');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('❗ Lūdzu, ievadi derīgu e-pasta adresi.');
        return;
    }

    fetch('/api/auth/register.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name, phone, email, password })
    })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}. Atbilde: ${text}`);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('Reģistrācijas atbilde:', data);
            if (data.success && data.token) {
                localStorage.setItem('auth_token', data.token);
                currentUser = data.user || { name, email, phone };
                userRole = 'client';
                
                clearForm('register');
                closeModal(); // Aizveram modal
                
                alert(`🎉 Sveiks, ${name}! Reģistrācija veiksmīga!\n\n📅 Tagad vari izvēlēties datumu rezervācijai.\n👀 Tavs rezervācijas vēsturi varēsi redzēt pogā "Manas rezervācijas".`);
                showCalendarView();
            } else {
                alert('❌ ' + (data.error || 'Reģistrācija neizdevās.'));
            }
        })
        .catch(err => {
            console.error('Reģistrācijas kļūda:', err);
            alert('❌ Reģistrācija neizdevās: ' + err.message);
        });
}

// Uzlabojam loginUser funkciju
function loginUser() {
    const email = document.getElementById('login-email')?.value.trim();
    const password = document.getElementById('login-password')?.value.trim();

    if (!email || !password) {
        alert('❗ Lūdzu, aizpildi visus laukus.');
        return;
    }

    fetch('/api/auth/login.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success && data.token) {
                localStorage.setItem('auth_token', data.token);
                currentUser = data.user;
                userRole = 'client';
                
                clearForm('login');
                closeModal(); // Aizveram modal
                
                showCalendarView();
                alert(`👋 Sveiks atpakaļ, ${currentUser.name || 'lietotāj'}!\n\n📅 Izvēlies datumu jaunai rezervācijai vai apskati savas esošās rezervācijas.`);
            } else {
                alert('❌ ' + (data.error || 'Pieteikšanās neizdevās.'));
            }
        })
        .catch(err => {
            console.error('Pieteikšanās kļūda:', err);
            alert('❌ Pieteikšanās neizdevās: ' + err.message);
        });
}