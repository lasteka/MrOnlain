// /nails-booking/public/account.js
function showLogin() {
    hideAuthForms();
    document.getElementById('login-form').classList.remove('hidden');
}

function showRegister() {
    hideAuthForms();
    document.getElementById('register-form').classList.remove('hidden');
}

function hideAuthForms() {
    document.getElementById('login-form')?.classList.add('hidden');
    document.getElementById('register-form')?.classList.add('hidden');
    document.getElementById('step-calendar')?.classList.remove('hidden');
    document.getElementById('auth-buttons')?.classList.remove('hidden');
}

function registerUser() {
    const name = document.getElementById('reg-name')?.value.trim();
    const phone = document.getElementById('reg-phone')?.value.trim();
    const email = document.getElementById('reg-email')?.value.trim();
    const password = document.getElementById('reg-password')?.value.trim();

    if (!name || !phone || !email || !password) {
        alert('Lūdzu, aizpildi visus laukus.');
        return;
    }
    if (!/^\+?\d{8,}$/.test(phone)) {
        alert('Telefonam jābūt vismaz 8 cipariem (ar vai bez + priekšā)');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Lūdzu, ievadi derīgu e-pasta adresi.');
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
            console.log('Reģistrācijas atbilde:', data); // Debug
            if (data.success && data.token) {
                console.log('Saglabā token:', data.token); // Debug
                localStorage.setItem('auth_token', data.token);
                
                // PIEVIENOTS: Pārbauda lietotāja lomu
                if (data.role === 'admin') {
                    window.location.href = '/nails-booking/admin/dashboard.html';
                } else {
                    // Parasti klients (role: 'client')
                    console.log('Izsauc loadUserBookings()'); // Debug
                    loadUserBookings();
                    document.getElementById('user-bookings').classList.remove('hidden');
                    document.getElementById('auth-buttons').classList.add('hidden');
                    hideAuthForms();
                }
            } else {
                alert(data.error || 'Reģistrācija neizdevās.');
            }
        })
        .catch(err => {
            console.error('Reģistrācijas kļūda:', err);
            alert('Reģistrācija neizdevās: ' + err.message);
        });
}

function loginUser() {
    const email = document.getElementById('login-email')?.value.trim();
    const password = document.getElementById('login-password')?.value.trim();

    if (!email || !password) {
        alert('Lūdzu, aizpildi visus laukus.');
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
                if (data.role === 'admin') {
                    window.location.href = data.redirect || '/nails-booking/admin/dashboard.html';
                } else {
                    loadUserBookings();
                    document.getElementById('user-bookings').classList.remove('hidden');
                    document.getElementById('auth-buttons').classList.add('hidden');
                    hideAuthForms();
                }
            } else {
                alert(data.error || 'Pieteikšanās neizdevās.');
            }
        })
        .catch(err => {
            console.error('Pieteikšanās kļūda:', err);
            alert('Pieteikšanās neizdevās: ' + err.message);
        });
}

function logoutUser() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        document.getElementById('user-bookings').classList.add('hidden');
        document.getElementById('step-calendar').classList.remove('hidden');
        document.getElementById('auth-buttons').classList.remove('hidden');
        return;
    }

    fetch('/api/auth/logout.php', {
        method: 'POST',
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
        .then(res => res.json())
        .then(data => {
            localStorage.removeItem('auth_token');
            document.getElementById('user-bookings').classList.add('hidden');
            document.getElementById('step-calendar').classList.remove('hidden');
            document.getElementById('auth-buttons').classList.remove('hidden');
        })
        .catch(err => {
            console.error('Izrakstīšanās kļūda:', err);
            localStorage.removeItem('auth_token');
            document.getElementById('user-bookings').classList.add('hidden');
            document.getElementById('step-calendar').classList.remove('hidden');
            document.getElementById('auth-buttons').classList.remove('hidden');
        });
}

function loadUserBookings() {
    const token = localStorage.getItem('auth_token');
    console.log('Token from localStorage:', token); // Debug
    
    if (!token) {
        console.log('Nav token - rāda login formu');
        showLogin();
        return;
    }

    console.log('Sūta pieprasījumu uz get-user-bookings.php'); // Debug
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
            document.getElementById('step-calendar').classList.add('hidden');
            document.getElementById('user-bookings').classList.remove('hidden');
            const list = document.getElementById('bookings-list');
            if (!list) {
                console.error('Rezervāciju saraksta elements nav atrasts!');
                alert('Kļūda: Rezervāciju saraksts nav atrasts.');
                return;
            }
            list.innerHTML = '';
            bookings.forEach(booking => {
                const div = document.createElement('div');
                div.className = 'booking';
                div.innerHTML = `
                    <p><strong>Datums:</strong> ${booking.date}</p>
                    <p><strong>Pakalpojums:</strong> ${booking.service}</p>
                    <p><strong>Laiks:</strong> ${booking.time}</p>
                    <p><strong>Komentārs:</strong> ${booking.comment || ''}</p>
                    ${booking.image ? `<img src="/public/uploads/${booking.image}" width="100">` : ''}
                    <textarea placeholder="Pievienot komentāru" id="comment-${booking.id}"></textarea>
                    <input type="file" id="image-${booking.id}" accept="image/*">
                    <button onclick="updateBooking(${booking.id})">Labot</button>
                    <button onclick="cancelBooking(${booking.id})">Atcelt</button>
                `;
                list.appendChild(div);
            });
        })
        .catch(err => {
            console.error('Kļūda ielādējot rezervācijas:', err);
            alert('Neizdevās ielādēt rezervācijas: ' + err.message);
            logoutUser();
        });
}

function updateBooking(id) {
    const comment = document.getElementById(`comment-${id}`)?.value.trim();
    const image = document.getElementById(`image-${id}`)?.files[0];
    const formData = new FormData();
    formData.append('id', id);
    formData.append('comment', comment || '');
    if (image) formData.append('image', image);

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
                alert('Rezervācija atjaunināta!');
                loadUserBookings();
            } else {
                alert(data.error || 'Kļūda atjauninot rezervāciju.');
            }
        })
        .catch(err => {
            console.error('Kļūda atjauninot rezervāciju:', err);
            alert('Kļūda atjauninot rezervāciju: ' + err.message);
        });
}

function cancelBooking(id) {
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
                alert('Rezervācija atcelta!');
                loadUserBookings();
            } else {
                alert(data.error || 'Kļūda atceļot rezervāciju.');
            }
        })
        .catch(err => {
            console.error('Kļūda atceļot rezervāciju:', err);
            alert('Kļūda atceļot rezervāciju: ' + err.message);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        fetch('/api/auth/check-role.php', {  // Izņemts /nails-booking/ ceļš
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => {
                if (!res.ok) {
                    console.error(`HTTP kļūda: ${res.status} ${res.statusText}`);
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('Iegūtā loma:', data.role); // Debug izvade
                if (data.role === 'admin') {
                    window.location.href = '/nails-booking/admin/dashboard.html';
                } else if (data.role === 'client') {
                    loadUserBookings();
                } else {
                    console.error('Nezināma loma:', data.role);
                    localStorage.removeItem('auth_token');
                }
            })
            .catch(err => {
                console.error('Lomas pārbaudes kļūda:', err);
                localStorage.removeItem('auth_token');
            });
    }
});