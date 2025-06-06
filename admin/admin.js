// /admin/admin.js
// /admin/admin.js - Atjaunināts ar reālu PHP API integrāciju

// Aplikācijas globālie mainīgie
let currentUser = null;
let currentSection = 'dashboard';

// Inicializācija, kad lapa ir ielādēta
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Administrācijas panelis ielādējas...');
    
    // Pārbauda vai lietotājs ir autentificēts
    checkAuthentication();
    
    // Uzstāda navigācijas funkcionalitāti
    initializeNavigation();
    
    // Ielādē kontrolpaneļa datus
    loadDashboardData();
    
    // Uzstāda notikumu klausītājus
    setupEventListeners();
    
    console.log('✅ Administrācijas panelis gatavs');
});

// Autentifikācijas pārbaude ar reālu PHP API
function checkAuthentication() {
    const token = localStorage.getItem('admin_token');
    
    if (!token) {
        // Pārvirzīt uz login, ja nav token
        window.location.href = '/admin/login.php';
        return false;
    }
    
    // Pārbauda token ar PHP serveri
    fetch('/api/auth/check-role.php', {
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.role === 'admin') {
            currentUser = data.user;
            console.log('✅ Admin autentifikācija veiksmīga');
        } else {
            throw new Error('Nav admin tiesības');
        }
    })
    .catch(err => {
        console.error('❌ Autentifikācijas kļūda:', err);
        localStorage.removeItem('admin_token');
        window.location.href = '/admin/login.php';
    });
    
    return true;
}

// Izrakstīšanās funkcija
function logout() {
    if (confirm('🚪 Vai tiešām vēlies iziet no admin paneļa?')) {
        const token = localStorage.getItem('admin_token');
        
        // Informē serveri par logout
        if (token) {
            fetch('/api/auth/logout.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            }).catch(err => console.log('Logout error:', err));
        }
        
        // Notīra lokālos datus
        localStorage.removeItem('admin_token');
        currentUser = null;
        
        // Pārvirzīšana uz pieteikšanās lapu
        window.location.href = '/admin/login.php';
    }
}

// Navigācijas sistēmas inicializācija
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            if (section) {
                showSection(section);
            }
        });
    });
}

// Sadaļas rādīšanas funkcija
function showSection(sectionName) {
    // Atjaunina navigācijas stāvokli
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Slēpj visas sadaļas un parāda izvēlēto
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Atjaunina lapas virsrakstu
    const pageTitle = document.getElementById('page-title');
    const titles = {
        dashboard: 'Kontrolpanelis',
        bookings: 'Rezervācijas',
        services: 'Pakalpojumi',
        schedule: 'Darba laiki',
        clients: 'Klienti',
        settings: 'Iestatījumi'
    };
    
    if (pageTitle && titles[sectionName]) {
        pageTitle.textContent = titles[sectionName];
    }
    
    // Ielādē sadaļai specifiskos datus
    switch(sectionName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'bookings':
            loadBookingsData();
            break;
        case 'services':
            loadServicesData();
            break;
        case 'schedule':
            loadScheduleData();
            break;
        case 'clients':
            loadClientsData();
            break;
        case 'settings':
            loadSettingsData();
            break;
    }
    
    currentSection = sectionName;
}

// ========== KONTROLPANEĻA FUNKCIJAS ==========

// Kontrolpaneļa datu ielāde
function loadDashboardData() {
    updateStatsCards();
    loadRecentBookings();
}

// Statistikas karšu atjaunināšana (izmanto reālus PHP datus)
function updateStatsCards() {
    // Šodienas rezervācijas - reāls API izsaukums
    fetch('/api/admin/get-stats.php?stat=today_bookings', {
        headers: { 
            'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        const todayBookingsEl = document.getElementById('today-bookings');
        if (todayBookingsEl && data.count !== undefined) {
            todayBookingsEl.textContent = data.count;
        }
    })
    .catch(err => {
        console.error('Kļūda ielādējot šodienas rezervācijas:', err);
        // Fallback uz mock datiem
        document.getElementById('today-bookings').textContent = '2';
    });
    
    // Kopējais klientu skaits
    fetch('/api/admin/get-stats.php?stat=total_clients', {
        headers: { 
            'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        const totalClientsEl = document.getElementById('total-clients');
        if (totalClientsEl && data.count !== undefined) {
            totalClientsEl.textContent = data.count;
        }
    })
    .catch(err => {
        console.error('Kļūda ielādējot klientu skaitu:', err);
        document.getElementById('total-clients').textContent = '45';
    });
    
    // Aktīvo pakalpojumu skaits
    fetch('/api/bookings/get-services.php')
    .then(res => res.json())
    .then(data => {
        const activeServicesEl = document.getElementById('active-services');
        if (activeServicesEl) {
            const services = data.services || data || [];
            activeServicesEl.textContent = services.length;
        }
    })
    .catch(err => {
        console.error('Kļūda ielādējot pakalpojumus:', err);
        document.getElementById('active-services').textContent = '6';
    });
}

// Jaunāko rezervāciju ielāde kontrolpanelī
function loadRecentBookings() {
    const recentBookingsEl = document.getElementById('recent-bookings');
    if (!recentBookingsEl) return;
    
    // Izmanto esošo PHP API
    fetch('/api/admin/get-recent-bookings.php', {
        headers: { 
            'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    })
    .then(bookings => {
        recentBookingsEl.innerHTML = '';
        
        if (!bookings || bookings.length === 0) {
            recentBookingsEl.innerHTML = '<tr><td colspan="6">Nav jaunāku rezervāciju</td></tr>';
            return;
        }
        
        bookings.slice(0, 5).forEach(booking => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${booking.client_name || booking.name || 'Nav norādīts'}</td>
                <td>${booking.service || 'Nav norādīts'}</td>
                <td>${formatDate(booking.date)}</td>
                <td>${booking.time}</td>
                <td>${getStatusBadge(booking.status || 'pending')}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editBooking(${booking.id})">
                        ✏️ Rediģēt
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="cancelBooking(${booking.id})">
                        ❌ Atcelt
                    </button>
                </td>
            `;
            recentBookingsEl.appendChild(row);
        });
    })
    .catch(err => {
        console.error('Kļūda ielādējot jaunākās rezervācijas:', err);
        recentBookingsEl.innerHTML = '<tr><td colspan="6">Kļūda ielādējot datus</td></tr>';
    });
}

// ========== PAKALPOJUMU PĀRVALDĪBA ==========

// Pakalpojumu datu ielāde
function loadServicesData() {
    const servicesListEl = document.getElementById('services-list');
    if (!servicesListEl) return;
    
    // Izmanto esošo API
    fetch('/api/bookings/get-services.php')
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        const services = data.services || data || [];
        servicesListEl.innerHTML = '';
        
        if (services.length === 0) {
            servicesListEl.innerHTML = '<tr><td colspan="5">Nav pakalpojumu</td></tr>';
            return;
        }
        
        services.forEach(service => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${service.name}</td>
                <td>€${service.price}</td>
                <td>${service.duration} min</td>
                <td><span class="badge badge-success">Aktīvs</span></td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editService(${service.id})">
                        ✏️ Rediģēt
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteService(${service.id})">
                        🗑️ Dzēst
                    </button>
                </td>
            `;
            servicesListEl.appendChild(row);
        });
    })
    .catch(err => {
        console.error('Kļūda ielādējot pakalpojumus:', err);
        servicesListEl.innerHTML = '<tr><td colspan="5">Kļūda ielādējot datus</td></tr>';
    });
}

// Pakalpojuma pievienošanas modāla rādīšana
function showAddServiceModal() {
    const modal = document.getElementById('add-service-modal');
    if (modal) {
        modal.classList.add('active');
        // Notīra formas laukus
        document.getElementById('service-name').value = '';
        document.getElementById('service-price').value = '';
        document.getElementById('service-duration').value = '';
    }
}

// Pakalpojuma pievienošanas modāla slēpšana
function hideAddServiceModal() {
    const modal = document.getElementById('add-service-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Jauna pakalpojuma pievienošana
function addService() {
    const name = document.getElementById('service-name').value.trim();
    const price = parseFloat(document.getElementById('service-price').value);
    const duration = parseInt(document.getElementById('service-duration').value);
    
    // Validācija
    if (!name || !price || !duration) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }
    
    if (price <= 0 || duration <= 0) {
        showAlert('danger', 'Cena un ilgums jābūt lielākiem par 0!');
        return;
    }
    
    // Sūta uz PHP API
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', name);
    formData.append('price', price);
    formData.append('duration', duration);
    
    fetch('/api/admin/manage-services.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            hideAddServiceModal();
            showAlert('success', `Pakalpojums "${name}" pievienots!`);
            loadServicesData();
            updateStatsCards();
        } else {
            showAlert('danger', data.error || 'Kļūda pievienojot pakalpojumu');
        }
    })
    .catch(err => {
        console.error('Kļūda pievienojot pakalpojumu:', err);
        showAlert('danger', 'Kļūda pievienojot pakalpojumu: ' + err.message);
    });
}

// Pakalpojuma dzēšana
function deleteService(id) {
    if (confirm('Vai tiešām vēlies dzēst šo pakalpojumu? Šo darbību nevar atsaukt!')) {
        fetch(`/api/admin/manage-services.php?action=delete&id=${id}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                showAlert('success', 'Pakalpojums dzēsts!');
                loadServicesData();
                updateStatsCards();
            } else {
                showAlert('danger', data.error || 'Kļūda dzēšot pakalpojumu');
            }
        })
        .catch(err => {
            console.error('Kļūda dzēšot pakalpojumu:', err);
            showAlert('danger', 'Kļūda dzēšot pakalpojumu: ' + err.message);
        });
    }
}

// ========== REZERVĀCIJU PĀRVALDĪBA ==========

// Visu rezervāciju ielāde (placeholder - šis API vēl jāizveido)
function loadBookingsData() {
    const allBookingsEl = document.getElementById('all-bookings');
    if (!allBookingsEl) return;
    
    // Pagaidām izmanto mock datus, jo nav admin bookings API
    allBookingsEl.innerHTML = '<tr><td colspan="8">Rezervāciju pārvaldība tiks implementēta nākamajā versijā</td></tr>';
}

// ========== DARBA LAIKU PĀRVALDĪBA ==========

// Darba laiku datu ielāde (placeholder)
function loadScheduleData() {
    const scheduleListEl = document.getElementById('schedule-list');
    if (!scheduleListEl) return;
    
    scheduleListEl.innerHTML = '<tr><td colspan="5">Darba laiku pārvaldība tiks implementēta nākamajā versijā</td></tr>';
}

// ========== KLIENTU PĀRVALDĪBA ==========

// Klientu datu ielāde (placeholder)
function loadClientsData() {
    const clientsListEl = document.getElementById('clients-list');
    if (!clientsListEl) return;
    
    clientsListEl.innerHTML = '<tr><td colspan="6">Klientu pārvaldība tiks implementēta nākamajā versijā</td></tr>';
}

// ========== IESTATĪJUMU PĀRVALDĪBA ==========

// Iestatījumu datu ielāde
function loadSettingsData() {
    // Ielādē iestatījumus no localStorage vai noklusējuma vērtības
    const settings = JSON.parse(localStorage.getItem('studio_settings')) || {
        studioName: 'Nagu Studija',
        studioPhone: '+371 12345678',
        studioEmail: 'info@nagustudija.lv'
    };
    
    // Aizpilda formas laukus
    if (document.getElementById('studio-name')) {
        document.getElementById('studio-name').value = settings.studioName;
    }
    if (document.getElementById('studio-phone')) {
        document.getElementById('studio-phone').value = settings.studioPhone;
    }
    if (document.getElementById('studio-email')) {
        document.getElementById('studio-email').value = settings.studioEmail;
    }
}

// Iestatījumu saglabāšana
function saveSettings() {
    const settings = {
        studioName: document.getElementById('studio-name').value.trim(),
        studioPhone: document.getElementById('studio-phone').value.trim(),
        studioEmail: document.getElementById('studio-email').value.trim()
    };
    
    // Validācija
    if (!settings.studioName || !settings.studioPhone || !settings.studioEmail) {
        showAlert('danger', 'Visi pamatlauki ir obligāti!');
        return;
    }
    
    // Saglabā localStorage (pagaidām)
    localStorage.setItem('studio_settings', JSON.stringify(settings));
    
    showAlert('success', 'Iestatījumi saglabāti!');
}

// ========== PALĪGFUNKCIJAS ==========

// Datuma formatēšana
function formatDate(dateString) {
    if (!dateString) return 'Nav norādīts';
    const date = new Date(dateString);
    return date.toLocaleDateString('lv-LV');
}

// Statusa žetona ģenerēšana
function getStatusBadge(status) {
    const badges = {
        confirmed: '<span class="badge badge-success">Apstiprināts</span>',
        pending: '<span class="badge badge-warning">Gaida</span>',
        cancelled: '<span class="badge badge-danger">Atcelts</span>'
    };
    return badges[status] || '<span class="badge badge-warning">Gaida</span>';
}

// Brīdinājuma ziņojuma rādīšana
function showAlert(type, message) {
    // Izveido brīdinājuma elementu
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.maxWidth = '400px';
    alert.style.animation = 'slideIn 0.3s ease';
    
    document.body.appendChild(alert);
    
    // Automātiski noņem pēc 4 sekundēm
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }, 4000);
}

// Ielādes animācijas rādīšana
function showLoading(element) {
    if (element) {
        element.classList.add('loading');
    }
}

// Ielādes animācijas slēpšana
function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
    }
}

// ========== NOTIKUMU KLAUSĪTĀJI ==========

// Notikumu klausītāju uzstādīšana
function setupEventListeners() {
    // Mobilās izvēlnes pārslēgs
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        });
    }
    
    // Modālo logu aizvēršana, noklikšķinot ārpusē
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Tastatūras saīsnes
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Aizvērt visus modālos logus
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
    
    // Formu iesniegšanas apstrādātāji
    const addServiceForm = document.getElementById('add-service-form');
    if (addServiceForm) {
        addServiceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addService();
        });
    }
    
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }
}

// Datu automātiska atjaunināšana ik 30 sekundes
setInterval(() => {
    if (currentSection === 'dashboard') {
        updateStatsCards();
        loadRecentBookings();
    }
}, 30000);

// Funkciju eksportēšana globālajam scope
window.showSection = showSection;
window.logout = logout;
window.showAddServiceModal = showAddServiceModal;
window.hideAddServiceModal = hideAddServiceModal;
window.addService = addService;
window.deleteService = deleteService;
window.saveSettings = saveSettings;

// Placeholder funkcijas (tiks implementētas nākamajā versijā)
window.editService = function(id) { showAlert('info', 'Pakalpojuma rediģēšana tiks implementēta nākamajā versijā'); };
window.editBooking = function(id) { showAlert('info', 'Rezervācijas rediģēšana tiks implementēta nākamajā versijā'); };
window.cancelBooking = function(id) { showAlert('info', 'Rezervācijas atcelšana tiks implementēta nākamajā versijā'); };

console.log('✅ Admin JavaScript ielādēts veiksmīgi');