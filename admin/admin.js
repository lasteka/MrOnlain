// admin.js - Atjauninātais ar universālu konfigurāciju

// ========================================
// 1. DEBUGGING UN PAMATA FUNKCIJAS
// ========================================

function debugAuth() {
    const token = localStorage.getItem('admin_token');
    console.log('🔍 Admin token:', token ? `${token.substring(0, 10)}...` : 'Nav atrasts');
    
    if (!token) {
        console.log('❌ Nav admin token - novirzi uz login lapu');
        window.location.href = AdminConfig.getAdminUrl('login.php');
        return false;
    }
    return true;
}

function testAPIConnection() {
    if (!debugAuth()) return;
    
    AdminConfig.apiCall('get-stats.php?stat=today_bookings')
        .then(data => {
            console.log('✅ API Test uspešīgs:', data);
            showAlert('success', 'API savienojums darbojas!');
        })
        .catch(error => {
            console.error('❌ API Test failed:', error);
            showAlert('danger', 'API savienojuma kļūda: ' + error.message);
        });
}

// ========================================
// 2. ALERT SISTĒMA
// ========================================

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed; 
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        padding: 1rem 1.5rem; 
        border-radius: 8px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 400px;
        animation: slideInRight 0.3s ease;
        background: ${type === 'success' ? '#d4edda' : type === 'danger' ? '#f8d7da' : '#fff3cd'};
        border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'danger' ? '#f5c6cb' : '#ffeaa7'};
        color: ${type === 'success' ? '#155724' : type === 'danger' ? '#721c24' : '#856404'};
    `;
    
    alertDiv.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: 1rem; font-size: 1.2rem;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// ========================================
// 3. STATISTIKAS IELĀDE
// ========================================

function loadStatsCard(statType, elementId) {
    if (!debugAuth()) return;
    
    AdminConfig.apiCall(`get-stats.php?stat=${statType}`)
        .then(data => {
            console.log(`✅ Stats ${statType} data:`, data);
            
            const element = document.getElementById(elementId);
            if (element) {
                if (data.count !== undefined) {
                    element.textContent = data.count;
                } else if (data.revenue !== undefined) {
                    element.textContent = `€${data.revenue}`;
                } else {
                    element.textContent = JSON.stringify(data);
                }
                element.style.color = '';
            } else {
                console.warn(`⚠️ Element ar ID '${elementId}' nav atrasts`);
            }
        })
        .catch(error => {
            console.error(`❌ Stats ${statType} failed:`, error);
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = 'Kļūda';
                element.style.color = '#f56565';
            }
        });
}

function updateStatsCards() {
    console.log('📊 Ielādē visas statistikas...');
    
    loadStatsCard('today_bookings', 'today-bookings');
    loadStatsCard('total_clients', 'total-clients');
    loadStatsCard('active_services', 'active-services');
    loadStatsCard('weekly_revenue', 'weekly-revenue');
}

// ========================================
// 4. REZERVĀCIJU PĀRVALDĪBA
// ========================================

function loadRecentBookings() {
    if (!debugAuth()) return;
    
    const activeSection = document.querySelector('.section.active');
    const isDashboard = activeSection && activeSection.id === 'dashboard-section';
    const tbody = document.getElementById(isDashboard ? 'recent-bookings' : 'all-bookings');
    
    console.log(`📋 Ielādē rezervācijas. Aktīvā sadaļa: ${activeSection?.id}, Tabula: ${tbody?.id}`);
    
    if (!tbody) {
        console.warn(`⚠️ Rezervāciju tabula nav atrasta. Active section: ${activeSection?.id}`);
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="6">Ielādē...</td></tr>';
    
    AdminConfig.apiCall('get-recent-bookings.php')
        .then(bookings => {
            console.log(`✅ Recent bookings data (${bookings.length} ieraksti):`, bookings);
            window.lastBookingsData = bookings;
            
            if (bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">Nav rezervāciju</td></tr>';
                return;
            }
            
            tbody.innerHTML = bookings.map(booking => `
                <tr>
                    <td>${booking.client_name}</td>
                    <td>${booking.service}</td>
                    <td>${booking.date}</td>
                    <td>${booking.time}</td>
                    <td>
                        <span class="badge badge-${getStatusClass(booking.status)}">
                            ${getStatusText(booking.status)}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editBooking(${booking.id})">Rediģēt</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBooking(${booking.id})">Dzēst</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('❌ Recent bookings failed:', error);
            tbody.innerHTML = `<tr><td colspan="6" style="color: #f56565;">Kļūda ielādējot rezervācijas.</td></tr>`;
        });
}

function deleteBooking(id) {
    if (!confirm('Vai tiešām vēlies dzēst šo rezervāciju?')) return;
    
    AdminConfig.apiCall(`manage-bookings.php?action=delete&id=${id}`)
        .then(data => {
            if (data.success) {
                showAlert('success', 'Rezervācija dzēsta veiksmīgi!');
                loadRecentBookings();
                updateStatsCards();
            } else {
                showAlert('danger', 'Kļūda dzēšot rezervāciju');
            }
        })
        .catch(error => {
            console.error('❌ Delete booking failed:', error);
            showAlert('danger', 'Kļūda dzēšot rezervāciju: ' + error.message);
        });
}

function editBooking(id) {
    const booking = window.lastBookingsData?.find(b => b.id == id);
    if (!booking) {
        showAlert('danger', 'Rezervācija nav atrasta');
        return;
    }
    showEditBookingModal(booking);
}

// FIKSĒTS: Pievienojam rezervācijas rediģēšanas funkciju
function saveBookingChanges() {
    const id = document.getElementById('edit-booking-id').value;
    const client = document.getElementById('edit-booking-client').value.trim();
    const phone = document.getElementById('edit-booking-phone').value.trim();
    const service = document.getElementById('edit-booking-service').value;
    const date = document.getElementById('edit-booking-date').value;
    const time = document.getElementById('edit-booking-time').value;
    const status = document.getElementById('edit-booking-status').value;
    const comment = document.getElementById('edit-booking-comment').value.trim();

    // DEBUG: Log input values
    console.log('🔍 DEBUG - Booking values:', {
        id, client, phone, service, date, time, status, comment,
        timeType: typeof time,
        timeLength: time.length
    });

    if (!client || !phone || !service || !date || !time) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }

    // Noņem sekundes no laika, ja tādas ir (13:12:00 -> 13:12)
    let cleanTime = time;
    if (time.length > 5 && time.includes(':')) {
        cleanTime = time.substring(0, 5);
        console.log(`🔧 Cleaned time from "${time}" to "${cleanTime}"`);
    }

    // Pārbauda laika formātu
    const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test(cleanTime)) {
        console.error('❌ Invalid time format:', cleanTime);
        showAlert('danger', `Nederīgs laika formāts: ${cleanTime}`);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('client_name', client);
    formData.append('phone', phone);
    formData.append('service', service);
    formData.append('date', date);
    formData.append('time', cleanTime); // Izmanto attīrīto laiku
    formData.append('status', status);
    formData.append('comment', comment);

    // DEBUG: Log FormData
    console.log('🔍 DEBUG - Booking FormData entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value} (${typeof value})`);
    }

    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    delete options.headers['Content-Type'];

    console.log('📤 Sending booking update request...');

    AdminConfig.apiCall('manage-bookings.php', options)
        .then(data => {
            if (data && data.success) {
                hideEditBookingModal();
                showAlert('success', 'Rezervācija atjaunota veiksmīgi!');
                loadRecentBookings();
            } else {
                console.error('❌ Server response error:', data);
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Update booking failed:', error);
            showAlert('danger', 'Kļūda atjaunojot rezervāciju: ' + error.message);
        });
}

// ========================================
// 5. PAKALPOJUMU PĀRVALDĪBA  
// ========================================

function loadServicesData() {
    const servicesList = document.getElementById('services-list');
    if (!servicesList) return;
    
    AdminConfig.apiCall('get-services.php')
        .then(services => {
            window.allServicesData = services;

            servicesList.innerHTML = services.map(service => `
                <tr>
                    <td>${service.name}</td>
                    <td>€${service.price}</td>
                    <td>${service.duration} min</td>
                    <td><span class="badge badge-success">Aktīvs</span></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editService(${service.id})">Rediģēt</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteService(${service.id})">Dzēst</button>
                    </td>
                </tr>
            `).join('');

            // FIKSĒTS: Ielādējam pakalpojumus rezervāciju rediģēšanas formā
            loadServicesIntoEditForm(services);
        })
        .catch(error => {
            console.error('❌ Load services failed:', error);
            servicesList.innerHTML = '<tr><td colspan="5">Kļūda ielādējot pakalpojumus</td></tr>';
        });
}

// FIKSĒTS: Pievienojam pakalpojumus rezervāciju rediģēšanas formā
function loadServicesIntoEditForm(services) {
    const select = document.getElementById('edit-booking-service');
    if (select) {
        select.innerHTML = '<option value="">Izvēlies pakalpojumu</option>' +
            services.map(service => `<option value="${service.name}">${service.name} (€${service.price})</option>`).join('');
    }
}

function addService() {
    const name = document.getElementById('service-name').value.trim();
    const price = parseFloat(document.getElementById('service-price').value);
    const duration = parseInt(document.getElementById('service-duration').value);
    
    if (!name || !price || !duration || price <= 0 || duration <= 0) {
        showAlert('danger', 'Visi lauki ir obligāti un tiem jābūt derīgiem!');
        return;
    }

    if (window.allServicesData && window.allServicesData.some(service => service.name.toLowerCase() === name.toLowerCase())) {
        showAlert('danger', 'Pakalpojums ar šādu nosaukumu jau pastāv!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', name);
    formData.append('price', price);
    formData.append('duration', duration);
    
    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    
    delete options.headers['Content-Type'];
    
    AdminConfig.apiCall('manage-services.php', options)
        .then(data => {
            if (data && data.success) {
                hideAddServiceModal();
                showAlert('success', `Pakalpojums "${name}" pievienots veiksmīgi!`);
                loadServicesData();
                updateStatsCards();
                document.getElementById('add-service-form').reset();
            } else {
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Add service failed:', error);
            showAlert('danger', 'Kļūda pievienojot pakalpojumu: ' + error.message);
        });
}

function deleteService(id) {
    if (!confirm('Vai tiešām vēlies dzēst šo pakalpojumu?')) return;
    
    AdminConfig.apiCall(`manage-services.php?action=delete&id=${id}`)
        .then(data => {
            if (data.success) {
                showAlert('success', 'Pakalpojums dzēsts veiksmīgi!');
                loadServicesData();
                updateStatsCards();
            } else {
                showAlert('danger', 'Kļūda dzēšot pakalpojumu');
            }
        })
        .catch(error => {
            console.error('❌ Delete service failed:', error);
            showAlert('danger', 'Kļūda dzēšot pakalpojumu: ' + error.message);
        });
}

// FIKSĒTS: Pievienojam pakalpojuma rediģēšanas funkciju
function editService(id) {
    const service = window.allServicesData?.find(s => s.id == id);
    if (!service) {
        showAlert('danger', 'Pakalpojums nav atrasts');
        return;
    }
    showEditServiceModal(service);
}

function showEditServiceModal(service) {
    const modal = document.getElementById('edit-service-modal');
    if (!modal) {
        // Izveidojam modālu, ja nav
        createEditServiceModal();
    }
    
    document.getElementById('edit-service-id').value = service.id;
    document.getElementById('edit-service-name').value = service.name;
    document.getElementById('edit-service-price').value = service.price;
    document.getElementById('edit-service-duration').value = service.duration;
    document.getElementById('edit-service-modal').classList.add('active');
}

function hideEditServiceModal() {
    document.getElementById('edit-service-modal').classList.remove('active');
}

function saveServiceChanges() {
    const id = document.getElementById('edit-service-id').value;
    const name = document.getElementById('edit-service-name').value.trim();
    const price = parseFloat(document.getElementById('edit-service-price').value);
    const duration = parseInt(document.getElementById('edit-service-duration').value);

    if (!name || !price || !duration || price <= 0 || duration <= 0) {
        showAlert('danger', 'Visi lauki ir obligāti un tiem jābūt derīgiem!');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('name', name);
    formData.append('price', price);
    formData.append('duration', duration);

    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    delete options.headers['Content-Type'];

    AdminConfig.apiCall('manage-services.php', options)
        .then(data => {
            if (data && data.success) {
                hideEditServiceModal();
                showAlert('success', 'Pakalpojums atjaunots veiksmīgi!');
                loadServicesData();
            } else {
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Update service failed:', error);
            showAlert('danger', 'Kļūda atjaunojot pakalpojumu: ' + error.message);
        });
}

// ========================================
// 6. NAVIGĀCIJAS SISTĒMA
// ========================================

function showSection(sectionName) {
    console.log(`🔄 Pārslēdzas uz sadaļu: ${sectionName}`);
    
    document.querySelectorAll('.section').forEach(section => section.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
        console.log(`✅ Sadaļa aktivizēta: ${sectionName}-section`);
    } else {
        console.error(`❌ Sadaļa nav atrasta: ${sectionName}-section`);
    }
    
    const targetNav = document.querySelector(`[data-section="${sectionName}"]`);
    if (targetNav) {
        targetNav.classList.add('active');
    }
    
    const titles = {
        'dashboard': 'Kontrolpanelis', 
        'bookings': 'Rezervācijas', 
        'services': 'Pakalpojumi',
        'schedule': 'Darba laiki', 
        'clients': 'Klienti', 
        'settings': 'Iestatījumi'
    };
    const pageTitle = document.getElementById('page-title');
    if (pageTitle && titles[sectionName]) {
        pageTitle.textContent = titles[sectionName];
    }
    
    switch(sectionName) {
        case 'dashboard': 
            updateStatsCards(); 
            loadRecentBookings(); 
            break;
        case 'bookings': 
            console.log('📅 Ielādē rezervāciju sadaļu');
            loadRecentBookings(); 
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
    }
}

// ========================================
// 7. DARBA LAIKU PĀRVALDĪBA
// ========================================

function loadScheduleData() {
    const scheduleList = document.getElementById('schedule-list');
    if (!scheduleList) return;
    
    AdminConfig.apiCall('manage-hours.php?action=get')
        .then(hours => {
            window.allSchedulesData = hours;

            scheduleList.innerHTML = hours.map(hour => `
                <tr>
                    <td>${hour.formatted_date}</td>
                    <td>${hour.start_time}</td>
                    <td>${hour.end_time}</td>
                    <td><span class="badge badge-${hour.is_available ? 'success' : 'danger'}">${hour.is_available ? 'Pieejams' : 'Nepieejams'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editSchedule(${hour.id})">Rediģēt</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${hour.id})">Dzēst</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('❌ Load schedule failed:', error);
            scheduleList.innerHTML = '<tr><td colspan="5">Kļūda ielādējot darba laikus</td></tr>';
        });
}

function addSchedule() {
    const date = document.getElementById('schedule-date').value;
    const start = document.getElementById('schedule-start').value;
    const end = document.getElementById('schedule-end').value;
    const available = document.getElementById('schedule-available').checked;
    
    // DEBUG: Log input values
    console.log('🔍 DEBUG - Add schedule values:', {
        date, start, end, available,
        startType: typeof start,
        endType: typeof end
    });
    
    if (!date || !start || !end) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }
    
    if (start >= end) {
        showAlert('danger', 'Sākuma laikam jābūt pirms beigu laika!');
        return;
    }

    // Pārbauda laika formātu
    const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test(start)) {
        console.error('❌ Invalid start time format:', start);
        showAlert('danger', `Nederīgs sākuma laika formāts: ${start}`);
        return;
    }
    
    if (!timeRegex.test(end)) {
        console.error('❌ Invalid end time format:', end);
        showAlert('danger', `Nederīgs beigu laika formāts: ${end}`);
        return;
    }

    if (window.allSchedulesData && window.allSchedulesData.some(schedule => schedule.date === date)) {
        showAlert('warning', 'Šim datumam jau ir pievienots darba laiks! Tas tiks atjaunots.');
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('date', date);
    formData.append('start_time', start);
    formData.append('end_time', end);
    formData.append('is_available', available ? '1' : '0');
    
    // DEBUG: Log FormData
    console.log('🔍 DEBUG - Add FormData entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value} (${typeof value})`);
    }
    
    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    delete options.headers['Content-Type'];
    
    console.log('📤 Sending add schedule request...');
    
    AdminConfig.apiCall('manage-hours.php', options)
        .then(data => {
            if (data && data.success) {
                hideAddScheduleModal();
                showAlert('success', 'Darba laiks pievienots veiksmīgi!');
                loadScheduleData();
                document.getElementById('add-schedule-form').reset();
            } else {
                console.error('❌ Server response error:', data);
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Add schedule failed:', error);
            showAlert('danger', 'Kļūda pievienojot darba laiku: ' + error.message);
        });
}

function deleteSchedule(id) {
    if (!confirm('Vai tiešām vēlies dzēst šo darba laiku?')) return;
    
    AdminConfig.apiCall(`manage-hours.php?action=delete&id=${id}`)
        .then(data => {
            if (data.success) {
                showAlert('success', 'Darba laiks dzēsts veiksmīgi!');
                loadScheduleData();
            } else {
                showAlert('danger', 'Kļūda dzēšot darba laiku');
            }
        })
        .catch(error => {
            console.error('❌ Delete schedule failed:', error);
            showAlert('danger', 'Kļūda dzēšot darba laiku: ' + error.message);
        });
}

// FIKSĒTS: Pievienojam darba laika rediģēšanas funkciju
function editSchedule(id) {
    const schedule = window.allSchedulesData?.find(s => s.id == id);
    if (!schedule) {
        showAlert('danger', 'Darba laiks nav atrasts');
        return;
    }
    showEditScheduleModal(schedule);
}

function showEditScheduleModal(schedule) {
    const modal = document.getElementById('edit-schedule-modal');
    if (!modal) {
        createEditScheduleModal();
    }
    
    // Formatē laikus pareizi (noņem sekundes, ja tādas ir)
    let formattedStartTime = schedule.start_time;
    let formattedEndTime = schedule.end_time;
    
    if (schedule.start_time && schedule.start_time.length > 5) {
        formattedStartTime = schedule.start_time.substring(0, 5);
    }
    
    if (schedule.end_time && schedule.end_time.length > 5) {
        formattedEndTime = schedule.end_time.substring(0, 5);
    }
    
    console.log('📝 Setting schedule modal values:', {
        original_start: schedule.start_time,
        original_end: schedule.end_time,
        formatted_start: formattedStartTime,
        formatted_end: formattedEndTime
    });
    
    document.getElementById('edit-schedule-id').value = schedule.id;
    document.getElementById('edit-schedule-date').value = schedule.date;
    document.getElementById('edit-schedule-start').value = formattedStartTime;
    document.getElementById('edit-schedule-end').value = formattedEndTime;
    document.getElementById('edit-schedule-available').checked = schedule.is_available;
    document.getElementById('edit-schedule-modal').classList.add('active');
}

function hideEditScheduleModal() {
    document.getElementById('edit-schedule-modal').classList.remove('active');
}

function saveScheduleChanges() {
    const id = document.getElementById('edit-schedule-id').value;
    const date = document.getElementById('edit-schedule-date').value;
    const start = document.getElementById('edit-schedule-start').value;
    const end = document.getElementById('edit-schedule-end').value;
    const available = document.getElementById('edit-schedule-available').checked;

    // DEBUG: Log input values
    console.log('🔍 DEBUG - Schedule values:', {
        id, date, start, end, available,
        startType: typeof start,
        endType: typeof end
    });

    if (!date || !start || !end) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }

    if (start >= end) {
        showAlert('danger', 'Sākuma laikam jābūt pirms beigu laika!');
        return;
    }

    // Pārbauda laika formātu
    const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test(start)) {
        console.error('❌ Invalid start time format:', start);
        showAlert('danger', `Nederīgs sākuma laika formāts: ${start}`);
        return;
    }
    
    if (!timeRegex.test(end)) {
        console.error('❌ Invalid end time format:', end);
        showAlert('danger', `Nederīgs beigu laika formāts: ${end}`);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('date', date);
    formData.append('start_time', start);
    formData.append('end_time', end);
    formData.append('is_available', available ? '1' : '0');

    // DEBUG: Log FormData
    console.log('🔍 DEBUG - FormData entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value} (${typeof value})`);
    }

    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    delete options.headers['Content-Type'];

    console.log('📤 Sending schedule update request...');

    AdminConfig.apiCall('manage-hours.php', options)
        .then(data => {
            if (data && data.success) {
                hideEditScheduleModal();
                showAlert('success', 'Darba laiks atjaunots veiksmīgi!');
                loadScheduleData();
            } else {
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Update schedule failed:', error);
            showAlert('danger', 'Kļūda atjaunojot darba laiku: ' + error.message);
        });
}

// ========================================
// 8. KLIENTU PĀRVALDĪBA
// ========================================

function loadClientsData() {
    const clientsList = document.getElementById('clients-list');
    if (!clientsList) return;
    
    AdminConfig.apiCall('get-clients.php')
        .then(clients => {
            window.allClientsData = clients;

            if (clients.length === 0) {
                clientsList.innerHTML = '<tr><td colspan="6">Nav klientu</td></tr>';
                return;
            }

            clientsList.innerHTML = clients.map(client => `
                <tr>
                    <td>${client.name}</td>
                    <td>${client.email}</td>
                    <td>${client.phone}</td>
                    <td>${client.bookings_count || 0}</td>
                    <td>${client.last_visit || 'Nav bijis'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editClient(${client.id})">Rediģēt</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClient(${client.id})">Dzēst</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('❌ Load clients failed:', error);
            clientsList.innerHTML = '<tr><td colspan="6">Kļūda ielādējot klientus</td></tr>';
        });
}

function addClient() {
    const name = document.getElementById('client-name').value.trim();
    const email = document.getElementById('client-email').value.trim();
    const phone = document.getElementById('client-phone').value.trim();
    
    if (!name || !email || !phone) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }

    // Vienkārša email validācija
    if (!email.includes('@') || !email.includes('.')) {
        showAlert('danger', 'Nederīgs e-pasta formāts!');
        return;
    }

    if (window.allClientsData && window.allClientsData.some(client => 
        client.email.toLowerCase() === email.toLowerCase() || client.phone === phone)) {
        showAlert('danger', 'Klients ar šādu e-pastu vai telefonu jau pastāv!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('password', 'temp123'); // Temp parole
    
    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    
    delete options.headers['Content-Type'];
    
    AdminConfig.apiCall('manage-clients.php', options)
        .then(data => {
            if (data && data.success) {
                hideAddClientModal();
                showAlert('success', `Klients "${name}" pievienots veiksmīgi!`);
                loadClientsData();
                updateStatsCards();
                document.getElementById('add-client-form').reset();
            } else {
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Add client failed:', error);
            showAlert('danger', 'Kļūda pievienojot klientu: ' + error.message);
        });
}

function deleteClient(id) {
    if (!confirm('Vai tiešām vēlies dzēst šo klientu? Tas dzēsīs arī visas viņa rezervācijas!')) return;
    
    AdminConfig.apiCall(`manage-clients.php?action=delete&id=${id}`)
        .then(data => {
            if (data.success) {
                showAlert('success', 'Klients dzēsts veiksmīgi!');
                loadClientsData();
                updateStatsCards();
            } else {
                showAlert('danger', 'Kļūda dzēšot klientu');
            }
        })
        .catch(error => {
            console.error('❌ Delete client failed:', error);
            showAlert('danger', 'Kļūda dzēšot klientu: ' + error.message);
        });
}

function editClient(id) {
    const client = window.allClientsData?.find(c => c.id == id);
    if (!client) {
        showAlert('danger', 'Klients nav atrasts');
        return;
    }
    showEditClientModal(client);
}

function showEditClientModal(client) {
    const modal = document.getElementById('edit-client-modal');
    if (!modal) {
        createEditClientModal();
    }
    
    document.getElementById('edit-client-id').value = client.id;
    document.getElementById('edit-client-name').value = client.name;
    document.getElementById('edit-client-email').value = client.email;
    document.getElementById('edit-client-phone').value = client.phone;
    document.getElementById('edit-client-modal').classList.add('active');
}

function hideEditClientModal() {
    document.getElementById('edit-client-modal').classList.remove('active');
}

function saveClientChanges() {
    const id = document.getElementById('edit-client-id').value;
    const name = document.getElementById('edit-client-name').value.trim();
    const email = document.getElementById('edit-client-email').value.trim();
    const phone = document.getElementById('edit-client-phone').value.trim();

    if (!name || !email || !phone) {
        showAlert('danger', 'Visi lauki ir obligāti!');
        return;
    }

    if (!email.includes('@') || !email.includes('.')) {
        showAlert('danger', 'Nederīgs e-pasta formāts!');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);

    const options = {
        method: 'POST',
        body: formData,
        headers: AdminConfig.getHeaders(true)
    };
    delete options.headers['Content-Type'];

    AdminConfig.apiCall('manage-clients.php', options)
        .then(data => {
            if (data && data.success) {
                hideEditClientModal();
                showAlert('success', 'Klients atjaunots veiksmīgi!');
                loadClientsData();
            } else {
                showAlert('danger', data.error || data.message || 'Nezināma kļūda');
            }
        })
        .catch(error => {
            console.error('❌ Update client failed:', error);
            showAlert('danger', 'Kļūda atjaunojot klientu: ' + error.message);
        });
}

// ========================================
// 9. MODĀLIE LOGI
// ========================================

function showAddServiceModal() { document.getElementById('add-service-modal').classList.add('active'); }
function hideAddServiceModal() { document.getElementById('add-service-modal').classList.remove('active'); }
function showAddScheduleModal() { document.getElementById('add-schedule-modal').classList.add('active'); }
function hideAddScheduleModal() { document.getElementById('add-schedule-modal').classList.remove('active'); }
function showAddClientModal() { document.getElementById('add-client-modal').classList.add('active'); }
function hideAddClientModal() { document.getElementById('add-client-modal').classList.remove('active'); }

function showEditBookingModal(booking) {
    const modal = document.getElementById('edit-booking-modal');
    
    // Formatē laiku pareizi (noņem sekundes, ja tādas ir)
    let formattedTime = booking.time;
    if (booking.time && booking.time.length > 5) {
        formattedTime = booking.time.substring(0, 5);
    }
    
    console.log('📝 Setting booking modal values:', {
        original_time: booking.time,
        formatted_time: formattedTime
    });
    
    document.getElementById('edit-booking-id').value = booking.id;
    document.getElementById('edit-booking-client').value = booking.client_name;
    document.getElementById('edit-booking-phone').value = booking.client_phone;
    document.getElementById('edit-booking-service').value = booking.service;
    document.getElementById('edit-booking-date').value = booking.date;
    document.getElementById('edit-booking-time').value = formattedTime;
    document.getElementById('edit-booking-status').value = booking.status;
    document.getElementById('edit-booking-comment').value = booking.comment || '';
    modal.classList.add('active');
}

function hideEditBookingModal() { document.getElementById('edit-booking-modal').classList.remove('active'); }

// ========================================
// 10. DINAMISKIE MODĀLIE LOGI (ja nav HTML-ā)
// ========================================

function createEditServiceModal() {
    const modalHTML = `
    <div id="edit-service-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Rediģēt pakalpojumu</h3>
                <button class="close-btn" onclick="hideEditServiceModal()">&times;</button>
            </div>
            <form id="edit-service-form" onsubmit="event.preventDefault(); saveServiceChanges();">
                <input type="hidden" id="edit-service-id">
                <div class="form-group">
                    <label class="form-label">Pakalpojuma nosaukums</label>
                    <input type="text" class="form-control" id="edit-service-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cena (EUR)</label>
                    <input type="number" class="form-control" id="edit-service-price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ilgums (minūtes)</label>
                    <input type="number" class="form-control" id="edit-service-duration" min="1" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideEditServiceModal()">Atcelt</button>
                    <button type="submit" class="btn btn-primary">💾 Saglabāt</button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function createEditScheduleModal() {
    const modalHTML = `
    <div id="edit-schedule-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Rediģēt darba laiku</h3>
                <button class="close-btn" onclick="hideEditScheduleModal()">&times;</button>
            </div>
            <form id="edit-schedule-form" onsubmit="event.preventDefault(); saveScheduleChanges();">
                <input type="hidden" id="edit-schedule-id">
                <div class="form-group">
                    <label class="form-label">Datums</label>
                    <input type="date" class="form-control" id="edit-schedule-date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sākuma laiks</label>
                    <input type="time" class="form-control" id="edit-schedule-start" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Beigu laiks</label>
                    <input type="time" class="form-control" id="edit-schedule-end" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit-schedule-available">
                        Pieejams rezervācijām
                    </label>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideEditScheduleModal()">Atcelt</button>
                    <button type="submit" class="btn btn-primary">💾 Saglabāt</button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function createEditClientModal() {
    const modalHTML = `
    <div id="edit-client-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Rediģēt klientu</h3>
                <button class="close-btn" onclick="hideEditClientModal()">&times;</button>
            </div>
            <form id="edit-client-form" onsubmit="event.preventDefault(); saveClientChanges();">
                <input type="hidden" id="edit-client-id">
                <div class="form-group">
                    <label class="form-label">Vārds</label>
                    <input type="text" class="form-control" id="edit-client-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">E-pasts</label>
                    <input type="email" class="form-control" id="edit-client-email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefons</label>
                    <input type="tel" class="form-control" id="edit-client-phone" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideEditClientModal()">Atcelt</button>
                    <button type="submit" class="btn btn-primary">💾 Saglabāt</button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// ========================================
// 11. PALĪGFUNKCIJAS
// ========================================

function getStatusClass(status) {
    switch(status) {
        case 'confirmed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'warning';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'confirmed': return 'Apstiprināts';
        case 'cancelled': return 'Atcelts';
        default: return 'Gaida';
    }
}

function logout() {
    if (confirm('Vai tiešām vēlies iziet?')) {
        AdminConfig.logout();
    }
}

function toggleMobileMenu() {
    document.getElementById('sidebar').classList.toggle('active');
}

// ========================================
// 12. DEBUG RĪKI
// ========================================

function addTestButtons() {
    const container = document.querySelector('.content-header');
    if (!container || document.getElementById('debug-buttons')) return;
    
    const debugDiv = document.createElement('div');
    debugDiv.id = 'debug-buttons';
    debugDiv.style.cssText = 'margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;';
    
    debugDiv.innerHTML = `
        <button onclick="testAPIConnection()" class="btn btn-sm btn-warning">🧪 Test API</button>
        <button onclick="updateStatsCards()" class="btn btn-sm btn-info">📊 Reload Stats</button>
        <button onclick="loadRecentBookings()" class="btn btn-sm btn-info">📅 Reload Bookings</button>
        <button onclick="AdminConfig.debug()" class="btn btn-sm btn-secondary">🔍 Debug Config</button>
    `;
    
    container.appendChild(debugDiv);
}

// ========================================
// 13. INICIALIZĀCIJA
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Admin panel ielādējas...');
    if (!debugAuth()) return;
    
    console.log('🔗 Pievienojam navigācijas klausītājus...');
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            console.log(`🖱️ Noklikšķināts uz: ${section}`);
            showSection(section);
        });
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Formu event listeners
    document.getElementById('add-service-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        addService();
    });
    
    document.getElementById('add-schedule-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        addSchedule();
    });
    
    document.getElementById('add-client-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        addClient();
    });
    
    document.getElementById('edit-booking-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveBookingChanges();
    });
    
    console.log('📊 Ielādē sākuma datus...');
    updateStatsCards();
    loadRecentBookings();
    loadServicesData();
    loadScheduleData();
    
    showSection('dashboard');
    
    console.log('✅ Admin panel gatavs un navigācija darbojas');
    setTimeout(addTestButtons, 2000);
});

// ========================================
// 14. GLOBĀLO FUNKCIJU EKSPORTS
// ========================================

window.debugAuth = debugAuth;
window.testAPIConnection = testAPIConnection;
window.showAlert = showAlert;
window.updateStatsCards = updateStatsCards;
window.loadRecentBookings = loadRecentBookings;
window.addTestButtons = addTestButtons;
window.showSection = showSection;
window.addService = addService;
window.deleteService = deleteService;
window.editService = editService;
window.saveServiceChanges = saveServiceChanges;
window.loadServicesData = loadServicesData;
window.loadScheduleData = loadScheduleData;
window.addSchedule = addSchedule;
window.deleteSchedule = deleteSchedule;
window.editSchedule = editSchedule;
window.saveScheduleChanges = saveScheduleChanges;
window.loadClientsData = loadClientsData;
window.addClient = addClient;
window.deleteClient = deleteClient;
window.editClient = editClient;
window.saveClientChanges = saveClientChanges;
window.deleteBooking = deleteBooking;
window.editBooking = editBooking;
window.saveBookingChanges = saveBookingChanges;
window.showAddServiceModal = showAddServiceModal;
window.hideAddServiceModal = hideAddServiceModal;
window.showAddScheduleModal = showAddScheduleModal;
window.hideAddScheduleModal = hideAddScheduleModal;
window.showAddClientModal = showAddClientModal;
window.hideAddClientModal = hideAddClientModal;
window.showEditBookingModal = showEditBookingModal;
window.hideEditBookingModal = hideEditBookingModal;
window.showEditServiceModal = showEditServiceModal;
window.hideEditServiceModal = hideEditServiceModal;
window.showEditScheduleModal = showEditScheduleModal;
window.hideEditScheduleModal = hideEditScheduleModal;
window.showEditClientModal = showEditClientModal;
window.hideEditClientModal = hideEditClientModal;
window.logout = logout;
window.toggleMobileMenu = toggleMobileMenu;
window.getStatusClass = getStatusClass;
window.getStatusText = getStatusText;