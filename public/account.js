// public/account.js

// =================== GLOBĀLIE MAINĪGIE ===================
let currentUser = null;
let userRole = 'guest';

// =================== MODAL FUNKCIJAS (UZLABOTAS) ===================

// UZLABOTĀ modal atvēršana
function openModal() {
    console.log('🔓 Aktivizē modal overlay');
    document.body.classList.add('modal-open');
    
    // Papildu drošība - nodrošina ka body ir modal režīmā
    setTimeout(() => {
        if (!document.body.classList.contains('modal-open')) {
            document.body.classList.add('modal-open');
            console.warn('⚠️ Modal klase tika atkārtoti pievienota');
        }
    }, 10);
}

// UZLABOTĀ modal aizvēršana
function closeModal() {
    console.log('🔒 Aizvēr modal overlay');
    document.body.classList.remove('modal-open');
    
    // Papildu notīrīšana
    setTimeout(() => {
        if (document.body.classList.contains('modal-open')) {
            document.body.classList.remove('modal-open');
            console.warn('⚠️ Modal klase tika atkārtoti noņemta');
        }
    }, 10);
}

// UZLABOTĀ addCloseButtonToModal funkcija
function addCloseButtonToModal(modalElement) {
    if (!modalElement) return;
    
    const modalContent = modalElement.querySelector('.auth-form-content');
    if (!modalContent) {
        console.error('❌ Modal content nav atrasts!');
        return;
    }
    
    // Pārbauda vai aizvēršanas poga jau eksistē
    if (modalContent.querySelector('.close-btn')) {
        console.log('ℹ️ Close button jau eksistē');
        return;
    }
    
    // Izveido aizvēršanas pogu
    const closeBtn = document.createElement('button');
    closeBtn.className = 'close-btn';
    closeBtn.innerHTML = '&times;';
    closeBtn.type = 'button';
    closeBtn.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        hideAuthForms();
    };
    
    // Pievieno pogu modal content sākumā
    modalContent.insertBefore(closeBtn, modalContent.firstChild);
    console.log('✅ Close button pievienots');
}

// UZLABOTĀ showLogin funkcija 
function showLogin() {
    console.log('🔓 Atver login modal');
    hideAllViews();
    
    const loginForm = document.getElementById('login-form');
    if (!loginForm) {
        console.error('❌ Login forma nav atrasta!');
        return;
    }
    
    // Noņem hidden klasi UN parāda modal
    loginForm.classList.remove('hidden');
    
    // Pievieno aizvēršanas pogu
    addCloseButtonToModal(loginForm);
    
    // Aktivizē modal overlay
    openModal();
    
    // Debug info
    console.log('✅ Login modal atvērts, classes:', loginForm.className);
}

// UZLABOTĀ showRegister funkcija
function showRegister() {
    console.log('📝 Atver register modal');
    hideAllViews();
    
    const registerForm = document.getElementById('register-form');
    if (!registerForm) {
        console.error('❌ Register forma nav atrasta!');
        return;
    }
    
    // Noņem hidden klasi UN parāda modal
    registerForm.classList.remove('hidden');
    
    // Pievieno aizvēršanas pogu
    addCloseButtonToModal(registerForm);
    
    // Aktivizē modal overlay
    openModal();
    
    // Debug info
    console.log('✅ Register modal atvērts, classes:', registerForm.className);
}

// UZLABOTĀ hideAuthForms funkcija
function hideAuthForms() {
    console.log('❌ Slēpj auth formas');
    
    // Aizvēr modal overlay
    closeModal();
    
    // Paslēpj visas auth formas
    const authForms = ['login-form', 'register-form'];
    authForms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.classList.add('hidden');
            console.log(`✅ ${formId} paslēpts`);
        }
    });
    
    // Atgriežas uz kalendāru
    showCalendarView();
}

// =================== AUTENTIFIKĀCIJAS PĀRBAUDE ===================

// Pārbauda autentifikāciju lapas ielādes laikā
function checkAuthOnLoad() {
    const token = localStorage.getItem('auth_token');
    if (token) {
        fetch('/api/auth/check-role.php', {
            method: 'GET',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.user) {
                currentUser = data.user;
                userRole = data.role || 'client';
                updateAuthButtons();
                console.log('✅ Lietotājs autentificēts:', currentUser.name);
            } else {
                // Token nederīgs
                localStorage.removeItem('auth_token');
                currentUser = null;
                userRole = 'guest';
                updateAuthButtons();
            }
        })
        .catch(err => {
            console.error('Auth check kļūda:', err);
            localStorage.removeItem('auth_token');
            currentUser = null;
            userRole = 'guest';
            updateAuthButtons();
        });
    } else {
        updateAuthButtons();
    }
}

// Atjauno autentifikācijas pogas
function updateAuthButtons() {
    const authButtons = document.getElementById('auth-buttons');
    if (!authButtons) return;
    
    if (currentUser) {
        // Ielogots lietotājs
        authButtons.innerHTML = `
            <span style="margin-right: 10px; color: #e91e63; font-weight: 600;">
                👋 , ${currentUser.name}!
            </span>
            <button onclick="showUserBookings()" type="button">📋 Manas rezervācijas</button>
            <button onclick="logoutUser()" type="button">🚪 Iziet</button>
        `;
    } else {
        // Neielogots lietotājs
        authButtons.innerHTML = `
            <button onclick="showLogin()" type="button">Pieteikties</button>
            <button onclick="showRegister()" type="button">Reģistrēties</button>
        `;
    }
}

// =================== LIETOTĀJA REZERVĀCIJAS ===================

// KRITISKĀ FUNKCIJA - Parāda lietotāja rezervācijas
function showUserBookings() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        alert('❌ Nav autorizācijas! Lūdzu pieteikties vēlreiz.');
        showLogin();
        return;
    }
    
    hideAllViews();
    document.getElementById('user-bookings').classList.remove('hidden');
    
    // Parāda lietotāja vārdu
    const userNameDisplay = document.getElementById('user-name-display');
    if (userNameDisplay && currentUser) {
        userNameDisplay.textContent = currentUser.name;
    }
    
    // Ielādē rezervācijas
    loadUserBookings();
}

// KRITISKĀ FUNKCIJA - Ielādē lietotāja rezervācijas
function loadUserBookings() {
    const bookingsList = document.getElementById('bookings-list');
    if (!bookingsList) return;
    
    bookingsList.innerHTML = '<p>Ielādē rezervācijas...</p>';
    
    const token = localStorage.getItem('auth_token');
    
    fetch('/api/bookings/get-user-bookings.php', {
        method: 'GET',
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP kļūda: ${res.status}`);
        }
        return res.json();
    })
    .then(bookings => {
        console.log('📋 Saņemtās rezervācijas:', bookings);
        
        if (!Array.isArray(bookings) || bookings.length === 0) {
            bookingsList.innerHTML = `
                <div class="no-bookings">
                    <h3>📭 Nav rezervāciju</h3>
                    <p>Tu vēl neesi veicis nevienu rezervāciju.</p>
                </div>
            `;
            return;
        }
        
        bookingsList.innerHTML = bookings.map(booking => `
            <div class="booking" id="booking-${booking.id}">
                <h4>💅 ${booking.service}</h4>
                <div class="booking-info">
                    <p><strong>📅 Datums:</strong> ${booking.date}</p>
                    <p><strong>🕐 Laiks:</strong> ${booking.time}</p>
                    <p><strong>📞 Telefons:</strong> ${booking.phone || 'Nav norādīts'}</p>
                    <p><strong>💬 Komentārs:</strong> ${booking.comment || 'Nav komentāra'}</p>
                    ${booking.image ? `<p><strong>📷 Bilde:</strong> <a href="/public/uploads/${booking.image}" target="_blank">Skatīt bildi</a></p>` : ''}
                </div>
                
                <div class="booking-edit" id="edit-${booking.id}" style="display: none;">
                    <textarea id="comment-${booking.id}" placeholder="Jaunais komentārs...">${booking.comment || ''}</textarea>
                    <label class="file-label">📷 Mainīt bildi:</label>
                    <input type="file" id="image-${booking.id}" accept="image/*">
                </div>
                
                <div class="booking-buttons">
                    <button class="update-btn" onclick="toggleEdit(${booking.id})" id="edit-btn-${booking.id}">✏️ Labot</button>
                    <button class="reschedule-btn" onclick="rescheduleBooking(${booking.id})">📅 Pārcelt</button>
                    <button class="cancel-btn" onclick="cancelBooking(${booking.id})">❌ Atcelt</button>
                </div>
            </div>
        `).join('');
    })
    .catch(err => {
        console.error('❌ Kļūda ielādējot rezervācijas:', err);
        bookingsList.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <p>❌ Neizdevās ielādēt rezervācijas.</p>
                <p style="font-size: 14px; color: #6c757d;">Kļūda: ${err.message}</p>
                <button onclick="loadUserBookings()" style="margin-top: 10px;">🔄 Mēģināt vēlreiz</button>
            </div>
        `;
    });
}

// =================== REZERVĀCIJU PĀRVALDĪBA ===================

function toggleEdit(bookingId) {
    const editDiv = document.getElementById(`edit-${bookingId}`);
    const editBtn = document.getElementById(`edit-btn-${bookingId}`);
    
    if (editDiv.style.display === 'none' || editDiv.style.display === '') {
        editDiv.style.display = 'block';
        editBtn.textContent = '💾 Saglabāt';
        editBtn.onclick = () => saveBookingChanges(bookingId);
    } else {
        editDiv.style.display = 'none';
        editBtn.textContent = '✏️ Labot';
        editBtn.onclick = () => toggleEdit(bookingId);
    }
}

function saveBookingChanges(bookingId) {
    const comment = document.getElementById(`comment-${bookingId}`).value.trim();
    const imageInput = document.getElementById(`image-${bookingId}`);
    const token = localStorage.getItem('auth_token');
    
    const formData = new FormData();
    formData.append('id', bookingId);
    formData.append('comment', comment);
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    fetch('/api/bookings/update-booking.php', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Rezervācija atjaunota!');
            loadUserBookings();
        } else {
            alert('❌ ' + (data.error || 'Neizdevās atjaunināt rezervāciju.'));
        }
    })
    .catch(err => {
        console.error('Kļūda atjauninot rezervāciju:', err);
        alert('❌ Neizdevās atjaunināt rezervāciju: ' + err.message);
    });
}

function rescheduleBooking(bookingId) {
    if (!confirm('🔄 Vai vēlies pārcelt šo rezervāciju uz citu datumu/laiku?')) return;
    
    localStorage.setItem('reschedule_booking_id', bookingId);
    showCalendarView();
}

function cancelBooking(bookingId) {
    if (!confirm('❌ Vai tiešām vēlies atcelt šo rezervāciju?')) return;
    
    const token = localStorage.getItem('auth_token');
    
    fetch(`/api/bookings/delete-booking.php?id=${bookingId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Rezervācija atcelta!');
            loadUserBookings();
        } else {
            alert('❌ ' + (data.error || 'Neizdevās atcelt rezervāciju.'));
        }
    })
    .catch(err => {
        console.error('Kļūda atceļot rezervāciju:', err);
        alert('❌ Neizdevās atcelt rezervāciju: ' + err.message);
    });
}

// =================== PIETEIKŠANĀS/REĢISTRĀCIJA ===================

// Uzlabojam logoutUser funkciju
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
                updateAuthButtons();
                
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
            updateAuthButtons();
            showCalendarView();
            alert('⚠️ Izrakstīšanās ar kļūdu, bet lokālie dati notīrīti.');
        });
    }
    
    localStorage.removeItem('auth_token');
    currentUser = null;
    userRole = 'guest';
    updateAuthButtons();
    
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
                updateAuthButtons();
                
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
                updateAuthButtons();
                
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

// =================== SKATU PĀRVALDĪBA (UZLABOTAS) ===================

// UZLABOTĀ hideAllViews funkcija
function hideAllViews() {
    console.log('👁️ Slēpj visus skatus');
    
    // Paslēpj visus soļus
    document.querySelectorAll('.step').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Paslēpj auth formas
    document.querySelectorAll('.auth-form').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Paslēpj user bookings
    const userBookings = document.getElementById('user-bookings');
    if (userBookings) {
        userBookings.classList.add('hidden');
    }
    
    console.log('✅ Visi skati paslēpti');
}

// UZLABOTĀ showCalendarView funkcija
function showCalendarView() {
    console.log('📅 Parāda kalendāra skatu');
    hideAllViews();
    
    const calendarStep = document.getElementById('step-calendar');
    if (calendarStep) {
        calendarStep.classList.remove('hidden');
        console.log('✅ Kalendārs parādīts');
    } else {
        console.error('❌ Kalendāra solis nav atrasts!');
    }
}

function clearForm(formType) {
    if (formType === 'login') {
        document.getElementById('login-email').value = '';
        document.getElementById('login-password').value = '';
    } else if (formType === 'register') {
        document.getElementById('reg-name').value = '';
        document.getElementById('reg-phone').value = '';
        document.getElementById('reg-email').value = '';
        document.getElementById('reg-password').value = '';
    }
}

// =================== EVENT LISTENERS (UZLABOTI) ===================

// Uzlabotie event listeners modal aizvēršanai
document.addEventListener('click', function(e) {
    // Aizvēr modal, ja noklikšķina uz overlay (bet ne uz content)
    if (e.target.classList.contains('auth-form')) {
        console.log('🖱️ Klikšķis uz modal overlay - aizvēr');
        hideAuthForms();
    }
});

// ESC taustiņš modal aizvēršanai
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.auth-form:not(.hidden)');
        if (openModal) {
            console.log('⌨️ ESC nospiests - aizvēr modal');
            hideAuthForms();
        }
    }
});

// =================== DEBUG FUNKCIJAS ===================

// Debug funkcija modal stāvokļa pārbaudei
function debugModalState() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const bodyHasModal = document.body.classList.contains('modal-open');
    
    console.log('🔍 Modal Debug State:');
    console.log('- Body modal-open:', bodyHasModal);
    console.log('- Login form hidden:', loginForm ? loginForm.classList.contains('hidden') : 'N/A');
    console.log('- Register form hidden:', registerForm ? registerForm.classList.contains('hidden') : 'N/A');
    
    return {
        bodyModal: bodyHasModal,
        loginHidden: loginForm ? loginForm.classList.contains('hidden') : null,
        registerHidden: registerForm ? registerForm.classList.contains('hidden') : null
    };
}

// Pievieno debug info uz lapas (izstrādes režīmam)
function addDebugInfo() {
    if (window.location.hostname === 'localhost') {
        const debugDiv = document.createElement('div');
        debugDiv.className = 'debug-modal-state';
        debugDiv.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            font-size: 12px;
            z-index: 9999;
            border-radius: 5px;
            display: none;
        `;
        document.body.appendChild(debugDiv);
        
        // Atjauno debug info katru sekundi
        setInterval(() => {
            const state = debugModalState();
            debugDiv.innerHTML = `
                Debug: Body Modal: ${state.bodyModal}<br>
                Login Hidden: ${state.loginHidden}<br>
                Register Hidden: ${state.registerHidden}
            `;
        }, 1000);
        
        // Parāda/slēpj debug ar Ctrl+D
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
            }
        });
    }
}

// =================== INICIALIZĀCIJA ===================

// INICIALIZĀCIJA - pārbauda autentifikāciju lapas ielādes laikā
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializē account sistēmu ar modal uzlabojumiem');
    
    // Pārbauda vai nepieciešamie elementi eksistē
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    if (!loginForm) console.error('❌ Login forma nav atrasta DOM!');
    if (!registerForm) console.error('❌ Register forma nav atrasta DOM!');
    
    // Pārbauda sākotnējo stāvokli
    debugModalState();
    
    // Pievieno debug funkcionalitāti (tikai development)
    addDebugInfo();
    
    // Pārbauda autentifikāciju
    checkAuthOnLoad();
    
    console.log('✅ Account sistēma ar modal uzlabojumiem inicializēta');
});