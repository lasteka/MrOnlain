// /nails-booking/public/account.js - IZLABOTS ar pareizajiem API ceļiem
let currentUser = null;
let userRole = 'guest'; // 'guest' vai 'client'

// =================== SĀKUMA SKATS - KALENDĀRS AR 2 POGĀM ===================
function initializePage() {
    showCalendarView();
    updateAuthButtons();
}

function showCalendarView() {
    hideAllViews();
    document.getElementById('step-calendar')?.classList.remove('hidden');
    updateAuthButtons();
    
    // Parāda lietotāja vārdu, ja ielogots
    if (currentUser) {
        const nameDisplay = document.getElementById('user-name-display');
        if (nameDisplay) {
            nameDisplay.textContent = currentUser.name || 'Lietotāj';
        }
    }
}

function hideAllViews() {
    // Paslēpj visus galvenos skatus
    const views = [
        'step-calendar', 'step-service', 'step-time', 'step-confirm', 'step-thankyou',
        'user-bookings', 'login-form', 'register-form'
    ];
    
    views.forEach(viewId => {
        const view = document.getElementById(viewId);
        if (view) view.classList.add('hidden');
    });
}

// =================== AUTENTIFIKĀCIJAS POGU PĀRVALDĪBA ===================
function updateAuthButtons() {
    const token = localStorage.getItem('auth_token');
    const authButtons = document.getElementById('auth-buttons');
    
    if (!authButtons) return;
    
    // Noņem visas esošās dinamiskās pogas
    const dynamicButtons = authButtons.querySelectorAll('.dynamic-btn');
    dynamicButtons.forEach(btn => btn.remove());
    
    // Atrod pamata pogas
    let loginBtn = authButtons.querySelector('button[onclick="showLogin()"]');
    let registerBtn = authButtons.querySelector('button[onclick="showRegister()"]');
    
    if (token && currentUser) {
        // IELOGOTS LIETOTĀJS - paslēpj login/register, parāda user pogas
        if (loginBtn) loginBtn.classList.add('hidden');
        if (registerBtn) registerBtn.classList.add('hidden');
        
        // Izveido lietotāja pogas
        createUserButton('Manas rezervācijas', showUserBookings);
        createUserButton('Iziet', logoutUser);
        
    } else {
        // NAV IELOGOTS - parāda login/register
        if (loginBtn) loginBtn.classList.remove('hidden');
        if (registerBtn) registerBtn.classList.remove('hidden');
    }
}

function createUserButton(text, onclick) {
    const authButtons = document.getElementById('auth-buttons');
    const btn = document.createElement('button');
    btn.textContent = text;
    btn.onclick = onclick;
    btn.className = 'dynamic-btn';
    btn.type = 'button';
    authButtons.appendChild(btn);
}

// =================== AUTENTIFIKĀCIJAS FORMAS ===================
function showLogin() {
    hideAllViews();
    document.getElementById('login-form').classList.remove('hidden');
}

function showRegister() {
    hideAllViews();
    document.getElementById('register-form').classList.remove('hidden');
}

function hideAuthForms() {
    showCalendarView();
}

// =================== PALĪGFUNKCIJAS ===================
function clearForm(formType) {
    if (formType === 'login') {
        const emailField = document.getElementById('login-email');
        const passwordField = document.getElementById('login-password');
        if (emailField) emailField.value = '';
        if (passwordField) passwordField.value = '';
    } else if (formType === 'register') {
        const fields = ['reg-name', 'reg-phone', 'reg-email', 'reg-password'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.value = '';
        });
    }
}

// =================== REĢISTRĀCIJA ===================
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

    // IZLABOTS API CEĻŠ
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
                // Veiksmīga reģistrācija
                localStorage.setItem('auth_token', data.token);
                currentUser = data.user || { name, email, phone };
                userRole = 'client';
                
                // Notīra formas laukus
                clearForm('register');
                
                // Atgriež uz kalendāru ar lietotāja statusu
                showCalendarView();
                alert(`🎉 Sveiks, ${name}! Reģistrācija veiksmīga!\n\n📅 Tagad vari izvēlēties datumu rezervācijai.\n👀 Tavs rezervācijas vēsturi varēsi redzēt pogā "Manas rezervācijas".`);
            } else {
                alert('❌ ' + (data.error || 'Reģistrācija neizdevās.'));
            }
        })
        .catch(err => {
            console.error('Reģistrācijas kļūda:', err);
            alert('❌ Reģistrācija neizdevās: ' + err.message);
        });
}

// =================== PIETEIKŠANĀS ===================
function loginUser() {
    const email = document.getElementById('login-email')?.value.trim();
    const password = document.getElementById('login-password')?.value.trim();

    if (!email || !password) {
        alert('❗ Lūdzu, aizpildi visus laukus.');
        return;
    }

    // IZLABOTS API CEĻŠ
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
                // Veiksmīga pieteikšanās
                localStorage.setItem('auth_token', data.token);
                currentUser = data.user;
                userRole = 'client';
                
                // Notīra formas laukus
                clearForm('login');
                
                // Atgriež uz kalendāru
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

// =================== IZRAKSTĪŠANĀS ===================
function logoutUser() {
    if (!confirm('🚪 Vai tiešām vēlies iziet?')) {
        return;
    }
    
    const token = localStorage.getItem('auth_token');
    
    if (token) {
        // IZLABOTS API CEĻŠ
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
        })
        .catch(err => {
            console.error('Izrakstīšanās kļūda:', err);
        });
    }
    
    // Notīrīt lokālos datus
    localStorage.removeItem('auth_token');
    currentUser = null;
    userRole = 'guest';
    
    // Atgriezties uz kalendāru
    showCalendarView();
    alert('👋 Tu esi veiksmīgi izrakstījies! Vari turpināt veikt rezervācijas kā viesis vai pierakstīties atpakaļ.');
}

// =================== LIETOTĀJA REZERVĀCIJAS ===================
function showUserBookings() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        alert('🔐 Lūdzu, piesakies, lai redzētu savas rezervācijas.');
        showLogin();
        return;
    }
    
    loadUserBookings();
    hideAllViews();
    document.getElementById('user-bookings').classList.remove('hidden');
    
    // Atjaunina lietotāja vārdu
    const nameDisplay = document.getElementById('user-name-display');
    if (nameDisplay && currentUser) {
        nameDisplay.textContent = currentUser.name || 'Lietotāj';
    }
}

function loadUserBookings() {
    const token = localStorage.getItem('auth_token');
    console.log('Ielādē lietotāja rezervācijas...');
    
    if (!token) {
        console.log('Nav token - rāda login formu');
        showLogin();
        return;
    }

    // IZLABOTS API CEĻŠ
    fetch('/api/bookings/get-user-bookings.php', {
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(bookings => {
            const list = document.getElementById('bookings-list');
            if (!list) {
                console.error('Rezervāciju saraksta elements nav atrasts!');
                alert('❌ Kļūda: Rezervāciju saraksts nav atrasts.');
                return;
            }
            
            list.innerHTML = '';
            
            if (!bookings || bookings.length === 0) {
                list.innerHTML = `
                    <div class="no-bookings">
                        <h3>📅 Tev pagaidām nav rezervāciju</h3>
                        <p>Izvēlies datumu kalendārā, lai izveidotu savu pirmo rezervāciju!</p>
                    </div>
                `;
                return;
            }
            
            bookings.forEach(booking => {
                const div = document.createElement('div');
                div.className = 'booking';
                div.innerHTML = `
                    <div class="booking-info">
                        <h4>📋 Rezervācija #${booking.id}</h4>
                        <p><strong>📅 Datums:</strong> ${booking.date}</p>
                        <p><strong>💅 Pakalpojums:</strong> ${booking.service}</p>
                        <p><strong>🕐 Laiks:</strong> ${booking.time}</p>
                        <p><strong>💬 Komentārs:</strong> ${booking.comment || 'Nav pievienots'}</p>
                        ${booking.image ? `<div class="booking-image"><img src="/public/uploads/${booking.image}" width="100" alt="Rezervācijas attēls"></div>` : ''}
                    </div>
                    <div class="booking-actions">
                        <div class="booking-edit">
                            <textarea placeholder="Pievienot vai mainīt komentāru..." id="comment-${booking.id}" rows="3">${booking.comment || ''}</textarea>
                            <label for="image-${booking.id}" class="file-label">📷 Pievienot bildi:</label>
                            <input type="file" id="image-${booking.id}" accept="image/*">
                        </div>
                        <div class="booking-buttons">
                            <button onclick="updateBooking(${booking.id})" class="update-btn" type="button">💾 Saglabāt izmaiņas</button>
                            <button onclick="rescheduleBooking(${booking.id})" class="reschedule-btn" type="button">📅 Pārcelt</button>
                            <button onclick="cancelBooking(${booking.id})" class="cancel-btn" type="button">❌ Atcelt rezervāciju</button>
                        </div>
                    </div>
                `;
                list.appendChild(div);
            });
        })
        .catch(err => {
            console.error('Kļūda ielādējot rezervācijas:', err);
            alert('❌ Neizdevās ielādēt rezervācijas: ' + err.message);
            logoutUser();
        });
}

// =================== REZERVĀCIJU PĀRVALDĪBA ===================
function updateBooking(id) {
    const comment = document.getElementById(`comment-${id}`)?.value.trim();
    const image = document.getElementById(`image-${id}`)?.files[0];
    const formData = new FormData();
    formData.append('id', id);
    formData.append('comment', comment || '');
    if (image) formData.append('image', image);

    // IZLABOTS API CEĻŠ
    fetch('/api/bookings/update-booking.php', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` },
        body: formData
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ Rezervācija veiksmīgi atjaunināta!');
                loadUserBookings(); // Pārlādē rezervācijas
            } else {
                alert('❌ ' + (data.error || 'Kļūda atjauninot rezervāciju.'));
            }
        })
        .catch(err => {
            console.error('Kļūda atjauninot rezervāciju:', err);
            alert('❌ Kļūda atjauninot rezervāciju: ' + err.message);
        });
}

function rescheduleBooking(id) {
    if (!confirm('📅 Vai vēlies pārcelt šo rezervāciju? Tu varēsi izvēlēties jaunu datumu un laiku.')) {
        return;
    }
    
    // Saglabā rezervācijas ID pārcelšanai
    localStorage.setItem('reschedule_booking_id', id);
    alert('📅 Izvēlies jaunu datumu un laiku rezervācijas pārcelšanai.');
    
    // Aktivizē pārcelšanas režīmu
    window.isRescheduling = true;
    
    // Atgriež uz kalendāru
    showCalendarView();
}

function cancelBooking(id) {
    if (!confirm('❗ Vai tiešām vēlies atcelt šo rezervāciju?\n\n⚠️ Šo darbību nevar atsaukt!')) {
        return;
    }
    
    // IZLABOTS API CEĻŠ
    fetch(`/api/bookings/delete-booking.php?id=${id}`, {
        method: 'DELETE',
        headers: { 
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
        }
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ Rezervācija veiksmīgi atcelta!');
                loadUserBookings(); // Pārlādē rezervācijas
            } else {
                alert('❌ ' + (data.error || 'Kļūda atceļot rezervāciju.'));
            }
        })
        .catch(err => {
            console.error('Kļūda atceļot rezervāciju:', err);
            alert('❌ Kļūda atceļot rezervāciju: ' + err.message);
        });
}

// =================== NAVIGĀCIJA ===================
function backToCalendar() {
    // Notīra pārcelšanas režīmu
    window.isRescheduling = false;
    localStorage.removeItem('reschedule_booking_id');
    
    showCalendarView();
}

// =================== INICIALIZĀCIJA ===================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializē rezervāciju sistēmu...');
    
    // Pārbauda vai lietotājs jau ir ielogots
    const token = localStorage.getItem('auth_token');
    if (token) {
        // IZLABOTS API CEĻŠ
        fetch('/api/auth/check-role.php', {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('✅ Lietotājs jau ielogots:', data.role);
                currentUser = data.user;
                userRole = 'client'; // Vienmēr uzskata par klientu (nav admin funkcionalitātes)
                
                // Atgriež uz kalendāru ar lietotāja statusu
                showCalendarView();
            })
            .catch(err => {
                console.error('❌ Token nav derīgs:', err);
                // Dzēš nederīgo token
                localStorage.removeItem('auth_token');
                currentUser = null;
                userRole = 'guest';
                showCalendarView();
            });
    } else {
        // Nav ielogots - parāda pamatskatu
        console.log('👤 Viesis - nav ielogots');
        showCalendarView();
    }
});