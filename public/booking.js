// nails-booking/public/booking.js - strādājoša versija
let selectedDate = null;
let selectedService = null;
let selectedTime = null;
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth();
let tempBookingData = {}; // Pagaidu rezervācijas dati localStorage
let isRescheduling = false; // Vai pašlaik pārceļ rezervāciju

const monthNames = ["Janvāris", "Februāris", "Marts", "Aprīlis", "Maijs", "Jūnijs", "Jūlijs", "Augusts", "Septembris", "Oktobris", "Novembris", "Decembris"];

function generateCalendar(year, month) {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('Kalendāra elements nav atrasts!');
        return;
    }
    
    // Notīra kalendāru un iestatīta kā grid
    calendarEl.innerHTML = '';
    calendarEl.style.display = 'grid';
    calendarEl.style.gridTemplateColumns = 'repeat(7, 1fr)';
    calendarEl.style.gap = '8px';
    
    // Virsraksts
    const header = document.createElement('div');
    header.style.gridColumn = 'span 7';
    header.style.textAlign = 'center';
    header.innerHTML = `<h3>${monthNames[month]} ${year}</h3>`;
    calendarEl.appendChild(header);
    
    // Navigācijas pogas
    const nav = document.createElement('div');
    nav.className = 'calendar-nav';
    nav.style.gridColumn = 'span 7';
    nav.style.textAlign = 'center';
    nav.style.marginBottom = '15px';
    nav.innerHTML = `
        <button onclick="changeMonth(-1)" type="button">← Iepriekšējais</button>
        <button onclick="changeMonth(1)" type="button">Nākamais →</button>
    `;
    calendarEl.appendChild(nav);
    
    // Nedēļas dienu virsraksti
    const weekDays = ['P', 'O', 'T', 'C', 'P', 'S', 'Sv'];
    weekDays.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.textContent = day;
        dayHeader.style.textAlign = 'center';
        dayHeader.style.fontWeight = 'bold';
        dayHeader.style.padding = '10px';
        dayHeader.style.color = '#6c757d';
        calendarEl.appendChild(dayHeader);
    });

    const firstDay = new Date(year, month, 1).getDay() || 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    // Tukšās dienas
    for (let i = 1; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-empty';
        calendarEl.appendChild(empty);
    }

    // Mēneša dienas
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const btn = document.createElement('button');
        btn.textContent = day;
        btn.className = 'calendar-day';
        btn.type = 'button';
        
        // Neļaut izvēlēties pagātnes datumus
        if (dateStr < todayStr) {
            btn.disabled = true;
            btn.classList.add('past-date');
        } else {
            btn.onclick = () => selectDate(dateStr);
        }
        
        calendarEl.appendChild(btn);
    }
}

function selectDate(dateStr) {
    selectedDate = dateStr;
    tempBookingData.date = dateStr;
    localStorage.setItem('tempBookingData', JSON.stringify(tempBookingData));
    
    if (isRescheduling) {
        // Pārcelšanas režīmā - iet tieši uz laika izvēli
        const rescheduleId = localStorage.getItem('reschedule_booking_id');
        console.log('Pārceļ rezervāciju:', rescheduleId, 'uz datumu:', dateStr);
        // Šeit varētu ielādēt esošo pakalpojumu, bet pagaidām iet uz pakalpojumu izvēli
        nextStep('service');
    } else {
        // Parasta rezervācija
        nextStep('service');
    }
}

function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    } else if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentYear, currentMonth);
}

function nextStep(step) {
    // Paslēpj visus soļus
    document.querySelectorAll('.step').forEach(el => el.classList.add('hidden'));
    
    const stepEl = document.getElementById(`step-${step}`);
    if (!stepEl) {
        console.error(`Solis "${step}" nav atrasts!`);
        return;
    }
    
    stepEl.classList.remove('hidden');
    
    // Ielādē attiecīgo saturu
    switch(step) {
        case 'service':
            loadServices();
            const selectedDateEl = document.getElementById('selected-date');
            if (selectedDateEl) {
                selectedDateEl.textContent = selectedDate || '';
            }
            break;
        case 'time':
            loadAvailableTimes(selectedDate, selectedService);
            const timeDateEl = document.getElementById('time-date');
            const timeServiceEl = document.getElementById('time-service');
            if (timeDateEl) timeDateEl.textContent = selectedDate || '';
            if (timeServiceEl) timeServiceEl.textContent = selectedService || 'Nav izvēlēts';
            break;
        case 'confirm':
            showConfirmation();
            break;
    }
}

function goBackToServices() {
    nextStep('service');
}

function goBackToTime() {
    nextStep('time');
}

function loadServices() {
    const servicesList = document.getElementById('services-list');
    if (!servicesList) {
        console.error('Pakalpojumu saraksta elements nav atrasts!');
        return;
    }
    
    servicesList.innerHTML = '<p>Ielādē pakalpojumus...</p>';

    fetch('/api/bookings/get-services.php', {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('Saņemtie pakalpojumi:', data);
            const services = data.services || data || [];
            servicesList.innerHTML = '';
            
            if (!Array.isArray(services) || services.length === 0) {
                servicesList.innerHTML = '<p>Nav pieejamu pakalpojumu šim datumam.</p>';
                return;
            }
            
            services.forEach(service => {
                const serviceDiv = document.createElement('div');
                serviceDiv.className = 'service-option';
                serviceDiv.innerHTML = `
                    <h4>💅 ${service.name}</h4>
                    <p>💰 Cena: ${service.price} EUR</p>
                    <p>⏱️ Ilgums: ${service.duration} minūtes</p>
                    <button type="button" onclick="selectService('${service.name.replace(/'/g, "\\'")}')">Izvēlēties</button>
                `;
                servicesList.appendChild(serviceDiv);
            });
        })
        .catch(err => {
            console.error('Kļūda ielādējot pakalpojumus:', err);
            servicesList.innerHTML = '<p>❌ Neizdevās ielādēt pakalpojumus. Lūdzu, mēģiniet vēlāk.</p>';
        });
}

function selectService(serviceName) {
    selectedService = serviceName;
    tempBookingData.service = serviceName;
    localStorage.setItem('tempBookingData', JSON.stringify(tempBookingData));
    nextStep('time');
}

function loadAvailableTimes(date, service) {
    if (!date) {
        console.error('Datums nav norādīts!');
        return;
    }
    
    const timeSlotsEl = document.getElementById('time-slots');
    if (!timeSlotsEl) {
        console.error('Laika slotu elements nav atrasts!');
        return;
    }
    
    timeSlotsEl.innerHTML = '<p>Ielādē pieejamos laikus...</p>';
    
    // Debug - parāda kāds datums tiek sūtīts
    console.log('🔍 Ielādē laikus datumam:', date);
    console.log('🔍 Pakalpojums:', service);
    
    const url = `/api/bookings/get-availability.php?date=${encodeURIComponent(date)}`;
    console.log('🔍 Request URL:', url);
    
    fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
        .then(res => {
            console.log('🔍 Response status:', res.status);
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(times => {
            console.log('🔍 Saņemtie laiki (pilns response):', times);
            console.log('🔍 Laiku masīva tips:', typeof times);
            console.log('🔍 Vai ir masīvs:', Array.isArray(times));
            console.log('🔍 Masīva garums:', times ? times.length : 'nav masīvs');
            
            timeSlotsEl.innerHTML = '';
            
            // Pārbauda dažādus response formātus
            let timeSlots = times;
            if (times && times.times && Array.isArray(times.times)) {
                timeSlots = times.times;
                console.log('🔍 Izmanto times.times:', timeSlots);
            } else if (times && times.slots && Array.isArray(times.slots)) {
                timeSlots = times.slots;
                console.log('🔍 Izmanto times.slots:', timeSlots);
            }
            
            if (!timeSlots || !Array.isArray(timeSlots) || timeSlots.length === 0) {
                console.warn('⚠️ Nav pieejamu laiku vai nepareizs formāts');
                timeSlotsEl.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <p>❌ Nav pieejamu laiku šim datumam.</p>
                        <p style="font-size: 14px; color: #6c757d;">
                            Datums: ${date}<br>
                            Mēģini izvēlēties citu datumu.
                        </p>
                    </div>
                `;
                return;
            }
            
            console.log('✅ Ģenerē laika slotus:', timeSlots.length, 'gabali');
            
            timeSlots.forEach((slot, index) => {
                console.log(`🔍 Slot ${index}:`, slot);
                const btn = document.createElement('button');
                
                // Dažādi formāti
                let timeText = slot;
                if (typeof slot === 'object') {
                    timeText = slot.time || slot.hour || slot.slot || JSON.stringify(slot);
                }
                
                btn.textContent = timeText;
                btn.className = 'time-slot';
                btn.type = 'button';
                btn.onclick = () => selectTime(timeText);
                timeSlotsEl.appendChild(btn);
            });
        })
        .catch(err => {
            console.error('❌ Kļūda ielādējot laikus:', err);
            timeSlotsEl.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <p>❌ Neizdevās ielādēt pieejamos laikus.</p>
                    <p style="font-size: 14px; color: #6c757d;">
                        Kļūda: ${err.message}<br>
                        Datums: ${date}
                    </p>
                    <button onclick="loadAvailableTimes('${date}', '${service}')" style="margin-top: 10px;">
                        🔄 Mēģināt vēlreiz
                    </button>
                </div>
            `;
        });
}

function selectTime(time) {
    selectedTime = time;
    tempBookingData.time = time;
    localStorage.setItem('tempBookingData', JSON.stringify(tempBookingData));
    
    if (isRescheduling) {
        // Apstiprina pārcelšanu
        confirmReschedule();
    } else {
        // Parasta rezervācija
        nextStep('confirm');
    }
}

function showConfirmation() {
    const summaryEl = document.getElementById('booking-summary');
    const guestInfoEl = document.getElementById('guest-info');
    const userInfoEl = document.getElementById('user-info');
    
    if (!summaryEl) return;
    
    // Parāda rezervācijas kopsavilkumu
    summaryEl.innerHTML = `
        <div class="booking-summary">
            <h3>📋 Rezervācijas kopsavilkums</h3>
            <p><strong>📅 Datums:</strong> ${selectedDate}</p>
            <p><strong>💅 Pakalpojums:</strong> ${selectedService}</p>
            <p><strong>🕐 Laiks:</strong> ${selectedTime}</p>
        </div>
    `;
    
    // Pārbauda lietotāja statusu
    const token = localStorage.getItem('auth_token');
    const isLoggedIn = token && (typeof currentUser !== 'undefined' && currentUser);
    
    console.log('🔍 Auth check:', { token: !!token, currentUser: !!currentUser, isLoggedIn });
    
    // PASLĒPJ ABAS FORMAS VISPIRMS
    if (guestInfoEl) {
        guestInfoEl.classList.add('hidden');
        guestInfoEl.style.display = 'none';
    }
    if (userInfoEl) {
        userInfoEl.classList.add('hidden');
        userInfoEl.style.display = 'none';
    }
    
    // PARĀDA TIKAI PAREIZO FORMU
    if (isLoggedIn) {
        // REĢISTRĒTS LIETOTĀJS - rāda tikai user formu
        console.log('✅ Parāda reģistrētā lietotāja formu');
        if (userInfoEl) {
            userInfoEl.classList.remove('hidden');
            userInfoEl.style.display = 'block';
        }
    } else {
        // NEREĢISTRĒTS LIETOTĀJS - rāda tikai guest formu
        console.log('👤 Parāda nereģistrētā lietotāja formu');
        if (guestInfoEl) {
            guestInfoEl.classList.remove('hidden');
            guestInfoEl.style.display = 'block';
        }
    }
}

function confirmBooking() {
    const token = localStorage.getItem('auth_token');
    // Drošāka pārbaude
    const isLoggedIn = token && (typeof currentUser !== 'undefined' && currentUser);
    
    if (isLoggedIn) {
        // Reģistrēts lietotājs
        submitRegisteredUserBooking();
    } else {
        // Nereģistrēts lietotājs
        submitAnonymousBooking();
    }
}

function submitAnonymousBooking() {
    const name = document.getElementById('guest-name')?.value.trim();
    const phone = document.getElementById('guest-phone')?.value.trim();
    const comment = document.getElementById('guest-comment')?.value.trim();
    
    if (!name || !phone || !selectedDate || !selectedService || !selectedTime) {
        alert('❗ Vārds, telefons, datums, pakalpojums un laiks ir obligāti!');
        return;
    }
    
    if (!/^\+?\d{8,}$/.test(phone)) {
        alert('❗ Telefonam jābūt vismaz 8 cipariem (ar vai bez + priekšā)');
        return;
    }

    const booking = {
        name,
        phone,
        service: selectedService,
        date: selectedDate,
        time: selectedTime,
        comment: comment || ''
    };

    fetch('/api/bookings/submit-anonymous-booking.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(booking)
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                clearTempData();
                nextStep('thankyou');
            } else {
                alert('❌ ' + (data.error || 'Neizdevās veikt rezervāciju.'));
            }
        })
        .catch(err => {
            console.error('Kļūda veicot rezervāciju:', err);
            alert('❌ Neizdevās veikt rezervāciju: ' + err.message);
        });
}

function submitRegisteredUserBooking() {
    const comment = document.getElementById('user-comment')?.value.trim();
    const image = document.getElementById('user-image')?.files[0];
    
    // DEBUG - pārbauda token
    const token = localStorage.getItem('auth_token');
    console.log('🔍 Token from localStorage:', token ? token.substring(0, 20) + '...' : 'NAV TOKEN');
    console.log('🔍 currentUser:', currentUser);
    
    if (!token) {
        alert('❌ Nav autorizācijas token! Lūdzu pieteikties vēlreiz.');
        if (typeof showLogin === 'function') {
            showLogin();
        }
        return;
    }
    
    const formData = new FormData();
    formData.append('date', selectedDate);
    formData.append('service', selectedService);
    formData.append('time', selectedTime);
    formData.append('comment', comment || '');
    formData.append('auth_token', token); // PIEVIENOJAM TOKEN KĀ FORM DATA
    if (image) formData.append('image', image);
    
    console.log('🔍 Sūta uz submit-booking.php:');
    console.log('  - Date:', selectedDate);
    console.log('  - Service:', selectedService);
    console.log('  - Time:', selectedTime);
    console.log('  - Comment:', comment);
    console.log('  - Image:', image ? 'Jā (' + image.name + ')' : 'Nē');
    console.log('  - Authorization header:', `Bearer ${token.substring(0, 20)}...`);
    console.log('  - Token as FormData:', token.substring(0, 20) + '...');

    fetch('/api/bookings/submit-booking.php', {
        method: 'POST',
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        body: formData
    })
        .then(res => {
            console.log('🔍 Response status:', res.status);
            console.log('🔍 Response headers:', res.headers);
            
            if (!res.ok) {
                return res.text().then(text => {
                    console.log('🔍 Error response body:', text);
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('🔍 Success response:', data);
            if (data.success) {
                clearTempData();
                nextStep('thankyou');
            } else {
                alert('❌ ' + (data.error || 'Neizdevās veikt rezervāciju.'));
            }
        })
        .catch(err => {
            console.error('❌ Kļūda veicot rezervāciju:', err);
            alert('❌ Neizdevās veikt rezervāciju: ' + err.message);
        });
}

function confirmReschedule() {
    const rescheduleId = localStorage.getItem('reschedule_booking_id');
    if (!rescheduleId) {
        alert('❌ Kļūda: Nav atrasta rezervācija pārcelšanai');
        return;
    }

    const formData = new FormData();
    formData.append('id', rescheduleId);
    formData.append('date', selectedDate);
    formData.append('time', selectedTime);
    if (selectedService) {
        formData.append('service', selectedService);
    }

    fetch('/api/bookings/update-booking.php', {
        method: 'POST',
        headers: { 
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
        },
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
                alert('✅ Rezervācija veiksmīgi pārcelta!');
                localStorage.removeItem('reschedule_booking_id');
                isRescheduling = false;
                // Atgriež uz rezervāciju sarakstu
                if (typeof showUserBookings === 'function') {
                    showUserBookings();
                } else {
                    resetBooking();
                }
            } else {
                alert('❌ ' + (data.error || 'Neizdevās pārcelt rezervāciju.'));
            }
        })
        .catch(err => {
            console.error('Kļūda pārceļot rezervāciju:', err);
            alert('❌ Neizdevās pārcelt rezervāciju: ' + err.message);
        });
}

function editBooking() {
    // Atgriež uz pakalpojumu izvēli
    nextStep('service');
}

function cancelTempBooking() {
    if (confirm('❗ Vai tiešām vēlies atcelt rezervāciju?')) {
        clearTempData();
        resetBooking();
    }
}

function clearTempData() {
    selectedDate = null;
    selectedService = null;
    selectedTime = null;
    tempBookingData = {};
    localStorage.removeItem('tempBookingData');
}

function resetBooking() {
    clearTempData();
    isRescheduling = false;
    localStorage.removeItem('reschedule_booking_id');
    
    // Atgriež uz kalendāru
    if (typeof showCalendarView === 'function') {
        showCalendarView();
    } else {
        // Fallback - parāda kalendāra soli
        document.querySelectorAll('.step').forEach(el => el.classList.add('hidden'));
        const calendarStep = document.getElementById('step-calendar');
        if (calendarStep) {
            calendarStep.classList.remove('hidden');
        }
    }
}

function backToCalendar() {
    resetBooking();
}

// Inicializācija
document.addEventListener('DOMContentLoaded', () => {
    // Atjauno datus no localStorage, ja tādi ir
    const savedData = localStorage.getItem('tempBookingData');
    if (savedData) {
        try {
            tempBookingData = JSON.parse(savedData);
            selectedDate = tempBookingData.date;
            selectedService = tempBookingData.service;
            selectedTime = tempBookingData.time;
        } catch (e) {
            console.error('Kļūda ielādējot saglabātos datus:', e);
            localStorage.removeItem('tempBookingData');
        }
    }
    
    // Pārbauda vai ir pārcelšanas režīms
    if (localStorage.getItem('reschedule_booking_id')) {
        isRescheduling = true;
    }
    
    // Ģenerē kalendāru
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        generateCalendar(currentYear, currentMonth);
    } else {
        console.error('Kalendāra elements nav atrasts ielādes laikā!');
    }
});