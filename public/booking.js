// nails-booking/public/booking.js - GALA VERSIJA bez kļūdām
let selectedDate = null;
let selectedService = null;
let selectedTime = null;
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth();
let tempBookingData = {}; // Pagaidu rezervācijas dati localStorage
let isRescheduling = false; // Vai pašlaik pārceļ rezervāciju

const monthNames = ["Janvāris", "Februāris", "Marts", "Aprīlis", "Maijs", "Jūnijs", "Jūlijs", "Augusts", "Septembris", "Oktobris", "Novembris", "Decembris"];

// SVARĪGI: Notīra reschedule ja nav ielogots
function checkAndClearInvalidReschedule() {
    const rescheduleId = localStorage.getItem('reschedule_booking_id');
    const authToken = localStorage.getItem('auth_token');
    
    if (rescheduleId && !authToken) {
        console.warn('⚠️ Atrasts reschedule ID bet nav auth token - notīra');
        localStorage.removeItem('reschedule_booking_id');
        isRescheduling = false;
        return true; // Notīrīts
    }
    return false; // Nav notīrīts
}

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
        // Pārbauda vai joprojām ir ielogots
        const authToken = localStorage.getItem('auth_token');
        if (!authToken) {
            alert('❌ Sesija beigusies! Lūdzu pieteikties vēlreiz lai pārceltu rezervāciju.');
            localStorage.removeItem('reschedule_booking_id');
            isRescheduling = false;
            if (typeof showLogin === 'function') {
                showLogin();
            }
            return;
        }
        
        // Pārcelšanas režīmā
        const rescheduleId = localStorage.getItem('reschedule_booking_id');
        console.log('📅 Pārceļ rezervāciju:', rescheduleId, 'uz datumu:', dateStr);
        nextStep('service');
    } else {
        // Parasta rezervācija
        console.log('📅 Izvēlēts datums:', dateStr);
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

// ATJAUNOTĀ loadServices funkcija ar uzlaboto kartīšu dizainu
function loadServices() {
    const servicesList = document.getElementById('services-list');
    if (!servicesList) {
        console.error('Pakalpojumu saraksta elements nav atrasts!');
        return;
    }
    
    // Loading stāvoklis ar jauko dizainu
    servicesList.innerHTML = '<p class="loading-state">🔄 Ielādē pakalpojumus...</p>';

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
            console.log('📋 Saņemtie pakalpojumi:', data);
            const services = data.services || data || [];
            servicesList.innerHTML = '';
            
            if (!Array.isArray(services) || services.length === 0) {
                servicesList.innerHTML = '<p class="empty-state">📭 Nav pieejamu pakalpojumu šim datumam.</p>';
                return;
            }
            
            // Ģenerē skaistas pakalpojumu kartes
            services.forEach((service, index) => {
                const serviceDiv = document.createElement('div');
                serviceDiv.className = 'service-option';
                
                // Pievienojam special klases (opcional)
                if (index === 0) serviceDiv.classList.add('popular'); // Pirmais = populārs
                if (service.price && parseFloat(service.price) > 50) serviceDiv.classList.add('premium'); // Dārgi = premium
                if (service.name.toLowerCase().includes('jaun')) serviceDiv.classList.add('new'); // Jauni pakalpojumi
                
                serviceDiv.innerHTML = `
                    <div class="service-content">
                        <h4>💅 ${service.name}</h4>
                        <p>💰 Cena: ${service.price} EUR</p>
                        <p>⏱️ Ilgums: ${service.duration} minūtes</p>
                    </div>
                    <div class="service-button-container">
                        <button type="button" onclick="selectService('${service.name.replace(/'/g, "\\'")}')">
                            ✨ Izvēlēties šo pakalpojumu
                        </button>
                    </div>
                `;
                servicesList.appendChild(serviceDiv);
            });
            
            // Pievienojam smooth scroll animation
            setTimeout(() => {
                const serviceCards = document.querySelectorAll('.service-option');
                serviceCards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.4s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100); // Staggered animation
                });
            }, 100);
        })
        .catch(err => {
            console.error('❌ Kļūda ielādējot pakalpojumus:', err);
            servicesList.innerHTML = `
                <div class="error-message">
                    <h3>❌ Neizdevās ielādēt pakalpojumus</h3>
                    <p>Kļūda: ${err.message}</p>
                    <button onclick="loadServices()" style="margin-top: 15px; padding: 10px 20px; border-radius: 25px; border: none; background: var(--primary-rose); color: white; cursor: pointer;">
                        🔄 Mēģināt vēlreiz
                    </button>
                </div>
            `;
        });
}

// ATJAUNOTĀ selectService funkcija ar smooth feedback
function selectService(serviceName) {
    selectedService = serviceName;
    tempBookingData.service = serviceName;
    localStorage.setItem('tempBookingData', JSON.stringify(tempBookingData));
    
    console.log('💅 Izvēlēts pakalpojums:', serviceName);
    
    // Visual feedback - highlight selected service
    const serviceCards = document.querySelectorAll('.service-option');
    serviceCards.forEach(card => {
        const h4 = card.querySelector('h4');
        if (h4 && h4.textContent.includes(serviceName)) {
            // Temporary success highlight
            card.style.background = 'linear-gradient(145deg, rgba(16, 185, 129, 0.1) 0%, rgba(255,255,255,0.9) 100%)';
            card.style.borderColor = 'var(--success)';
            card.style.transform = 'scale(1.05)';
            
            const button = card.querySelector('button');
            if (button) {
                button.textContent = '✅ Izvēlēts!';
                button.style.background = 'var(--success)';
            }
            
            // Reset after animation
            setTimeout(() => {
                nextStep('time');
            }, 800);
        } else {
            // Fade out non-selected
            card.style.opacity = '0.5';
            card.style.transform = 'scale(0.95)';
        }
    });
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
    
    timeSlotsEl.innerHTML = '<p class="loading-state">⏰ Ielādē pieejamos laikus...</p>';
    
    console.log('🔍 Ielādē laikus datumam:', date);
    console.log('🔍 Pakalpojums:', service);
    
    // Sūta pakalpojuma nosaukumu lai ņemtu vērā ilgumu
    const url = `/api/bookings/get-availability.php?date=${encodeURIComponent(date)}&service=${encodeURIComponent(service || '')}`;
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
            console.log('🔍 Saņemtie laiki:', times);
            timeSlotsEl.innerHTML = '';
            
            // Pārbauda response formātu
            let timeSlots = times;
            if (times && times.times && Array.isArray(times.times)) {
                timeSlots = times.times;
            } else if (times && times.slots && Array.isArray(times.slots)) {
                timeSlots = times.slots;
            }
            
            if (!timeSlots || !Array.isArray(timeSlots) || timeSlots.length === 0) {
                console.warn('⚠️ Nav pieejamu laiku');
                timeSlotsEl.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <h3>❌ Nav pieejamu laiku šim datumam</h3>
                        <p>Datums: ${date}<br>
                        Pakalpojums: ${service || 'Nav izvēlēts'}<br>
                        Mēģini izvēlēties citu datumu vai pakalpojumu.</p>
                    </div>
                `;
                return;
            }
            
            console.log('✅ Ģenerē laika slotus:', timeSlots.length, 'gabali');
            
            timeSlots.forEach((slot, index) => {
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
                
                // Staggered animation for time slots
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(10px)';
                btn.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    btn.style.opacity = '1';
                    btn.style.transform = 'translateY(0)';
                }, index * 50);
                
                timeSlotsEl.appendChild(btn);
            });
        })
        .catch(err => {
            console.error('❌ Kļūda ielādējot laikus:', err);
            timeSlotsEl.innerHTML = `
                <div class="error-message" style="grid-column: 1 / -1;">
                    <h3>❌ Neizdevās ielādēt pieejamos laikus</h3>
                    <p>Kļūda: ${err.message}<br>
                    Datums: ${date}<br>
                    Pakalpojums: ${service || 'Nav izvēlēts'}</p>
                    <button onclick="loadAvailableTimes('${date}', '${service || ''}')" style="margin-top: 15px; padding: 10px 20px; border-radius: 25px; border: none; background: var(--primary-rose); color: white; cursor: pointer;">
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
    
    console.log('🕐 Izvēlēts laiks:', time);
    
    // Visual feedback for time selection
    const timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(slot => {
        if (slot.textContent === time) {
            slot.classList.add('selected');
            slot.style.background = 'var(--success)';
            slot.style.color = 'white';
            slot.style.borderColor = 'var(--success)';
            slot.textContent = '✅ ' + time;
        } else {
            slot.style.opacity = '0.5';
            slot.style.transform = 'scale(0.95)';
        }
    });
    
    if (isRescheduling) {
        // Pārbauda vai joprojām ir ielogots
        const authToken = localStorage.getItem('auth_token');
        if (!authToken) {
            alert('❌ Sesija beigusies! Lūdzu pieteikties vēlreiz lai pārceltu rezervāciju.');
            localStorage.removeItem('reschedule_booking_id');
            isRescheduling = false;
            if (typeof showLogin === 'function') {
                showLogin();
            }
            return;
        }
        
        // Apstiprina pārcelšanu
        setTimeout(() => {
            confirmReschedule();
        }, 1000);
    } else {
        // Parasta rezervācija
        setTimeout(() => {
            nextStep('confirm');
        }, 1000);
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
    const isLoggedIn = token && (typeof currentUser !== 'undefined' && currentUser);
    
    console.log('📤 Sāk rezervācijas procesu, ielogots:', isLoggedIn);
    
    if (isLoggedIn) {
        // Reģistrēts lietotājs
        submitRegisteredUserBooking();
    } else {
        // Nereģistrēts lietotājs
        submitAnonymousBooking();
    }
}

function submitAnonymousBooking() {
    console.log('👤 Sūta anonīmo rezervāciju');
    
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

    console.log('📤 Sūta uz submit-anonymous-booking.php:', booking);

    fetch('/api/bookings/submit-anonymous-booking.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(booking)
    })
        .then(res => {
            console.log('📥 Response status:', res.status);
            if (!res.ok) {
                throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('✅ Success response:', data);
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

function submitRegisteredUserBooking() {
    console.log('🔐 Sūta reģistrētā lietotāja rezervāciju');
    
    const comment = document.getElementById('user-comment')?.value.trim();
    const image = document.getElementById('user-image')?.files[0];
    
    const token = localStorage.getItem('auth_token');
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
    if (image) formData.append('image', image);

    fetch('/api/bookings/submit-booking.php', {
        method: 'POST',
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        body: formData
    })
        .then(res => {
            console.log('📥 Response status:', res.status);
            if (!res.ok) {
                return res.text().then(text => {
                    console.log('❌ Error response:', text);
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}`);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('✅ Success response:', data);
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
    
    if (typeof showCalendarView === 'function') {
        showCalendarView();
    } else {
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

// BONUS: Funkcija smooth scroll uz pakalpojumiem (ja nepieciešams)
function scrollToServices() {
    const servicesSection = document.getElementById('step-service');
    if (servicesSection) {
        servicesSection.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
}

// BONUS: Pakalpojumu filtrēšana pēc cenas (ja nepieciešama)
function filterServicesByPrice(maxPrice = null) {
    const serviceCards = document.querySelectorAll('.service-option');
    
    serviceCards.forEach(card => {
        const priceText = card.querySelector('p:first-of-type')?.textContent;
        const price = priceText ? parseFloat(priceText.match(/[\d.]+/)?.[0]) : 0;
        
        if (maxPrice && price > maxPrice) {
            card.style.display = 'none';
        } else {
            card.style.display = 'flex';
        }
    });
}

// BONUS: Pakalpojumu meklēšana (ja nepieciešama)
function searchServices(searchTerm) {
    const serviceCards = document.querySelectorAll('.service-option');
    const term = searchTerm.toLowerCase();
    
    serviceCards.forEach(card => {
        const serviceName = card.querySelector('h4')?.textContent.toLowerCase();
        
        if (serviceName && serviceName.includes(term)) {
            card.style.display = 'flex';
            card.style.opacity = '1';
        } else {
            card.style.display = 'none';
        }
    });
}

// Inicializācija
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializē booking sistēmu ar uzlabotajām kartēm');
    
    // SVARĪGI: Notīra nederīgo reschedule stāvokli
    if (checkAndClearInvalidReschedule()) {
        console.log('🧹 Notīrīts nederīgais reschedule stāvoklis');
    }
    
    // Atjauno datus no localStorage
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
    
    // Pārbauda derīgo pārcelšanas režīmu
    const rescheduleId = localStorage.getItem('reschedule_booking_id');
    const authToken = localStorage.getItem('auth_token');
    
    if (rescheduleId && authToken) {
        isRescheduling = true;
        console.log('📅 Pārcelšanas režīms aktivizēts, ID:', rescheduleId);
    }
    
    // Ģenerē kalendāru
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        generateCalendar(currentYear, currentMonth);
    } else {
        console.error('Kalendāra elements nav atrasts!');
    }
});