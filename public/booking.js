// /kursa-darbi/nails-booking/public/booking.js
let selectedDate = null;
let selectedService = null;
let selectedTime = null;
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth();
const monthNames = ["Janvāris", "Februāris", "Marts", "Aprīlis", "Maijs", "Jūnijs", "Jūlijs", "Augusts", "Septembris", "Oktobris", "Novembris", "Decembris"];
// const BASE_URL = '/kursa-darbi/nails-booking'; // Pielāgots ceļš

function generateCalendar(year, month) {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('Kalendāra elements nav atrasts!');
        alert('Kļūda: Kalendāra elements nav atrasts.');
        return;
    }
    calendarEl.innerHTML = `<h3>${monthNames[month]} ${year}</h3>`;
    
    const nav = document.createElement('div');
    nav.className = 'calendar-nav';
    nav.innerHTML = `
        <button onclick="changeMonth(-1)">Iepriekšējais</button>
        <button onclick="changeMonth(1)">Nākamais</button>
    `;
    calendarEl.appendChild(nav);

    const firstDay = new Date(year, month, 1).getDay() || 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for (let i = 1; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-empty';
        calendarEl.appendChild(empty);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const btn = document.createElement('button');
        btn.textContent = day;
        btn.className = 'calendar-day';
        btn.onclick = () => {
            selectedDate = dateStr;
            localStorage.setItem('selectedDate', dateStr);
            nextStep('service');
        };
        calendarEl.appendChild(btn);
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
    document.querySelectorAll('.step').forEach(el => el.classList.add('hidden'));
    const stepEl = document.getElementById(`step-${step}`);
    if (!stepEl) {
        console.error(`Step elements "${step}" nav atrasts!`);
        alert(`Kļūda: Solis "${step}" nav atrasts.`);
        return;
    }
    stepEl.classList.remove('hidden');  
    if (step === 'service') {
        loadServices();
    } else if (step === 'time') {
        loadAvailableTimes(selectedDate);
    } else if (step === 'confirm') {
        showConfirmation();
    }
}

function goBack() {
    const steps = ['calendar', 'service', 'time', 'confirm'];
    const currentStep = Array.from(document.querySelectorAll('.step:not(.hidden)')).map(el => el.id.replace('step-', ''))[0];
    const currentIndex = steps.indexOf(currentStep);
    if (currentIndex > 0) {
        nextStep(steps[currentIndex - 1]);
    }
}

function loadServices() {
    const select = document.getElementById('service-select');
    if (!select) {
        console.error('Pakalpojumu izvēlnes elements nav atrasts!');
        alert('Kļūda: Pakalpojumu izvēlne nav atrasta.');
        return;
    }
    select.innerHTML = '<option value="" disabled selected>Izvēlies pakalpojumu</option>';

    fetch(`/api/bookings/get-services.php`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}. Atbilde: ${text.substring(0, 100)}...`);
                });
            }
            return res.json();
        })
        .then(data => {
            const services = data.services || [];
            if (!Array.isArray(services)) {
                console.warn('Pakalpojumu atbilde nav masīvs:', services);
                alert('Nav pieejamu pakalpojumu.');
                return;
            }
            services.forEach(service => {
                const option = document.createElement('option');
                option.value = service.name;
                option.textContent = `${service.name} (${service.price} EUR, ${service.duration} min)`;
                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Kļūda ielādējot pakalpojumus:', err);
            alert('Neizdevās ielādēt pakalpojumus: ' + err.message);
        });
}


function updateSelectedService() {
    selectedService = document.getElementById('service-select').value;
    localStorage.setItem('selectedService', selectedService);
    document.getElementById('confirm-service').textContent = selectedService;
}

function loadAvailableTimes(date) {
    if (!date) {
        console.error('Datums nav norādīts!');
        alert('Kļūda: Datums nav izvēlēts.');
        return;
    }
    
    fetch(`api/bookings/get-availability.php?date=${encodeURIComponent(date)}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}. Atbilde: ${text.substring(0, 100)}...`);
                });
            }
            return res.json();
        })
        .then(times => {
            const container = document.getElementById('time-slots');
            if (!container) {
                console.error('Laika slotu elements nav atrasts!');
                alert('Kļūda: Laika sloti nav atrasti.');
                return;
            }
            container.innerHTML = '';
            if (times.length === 0) {
                container.innerHTML = '<p>Nav pieejamu laiku šim datumam.</p>';
                return;
            }
            times.forEach(slot => {
                const btn = document.createElement('button');
                btn.textContent = slot.time;
                btn.onclick = () => {
                    selectedTime = slot.time;
                    localStorage.setItem('selectedTime', selectedTime);
                    nextStep('confirm');
                };
                container.appendChild(btn);
            });
        })
        .catch(err => {
            console.error('Kļūda ielādējot laikus:', err);
            alert('Neizdevās ielādēt pieejamos laikus: ' + err.message);
        });
}

function showConfirmation() {
    const confirmDate = document.getElementById('confirm-date');
    const confirmService = document.getElementById('confirm-service');
    const confirmTime = document.getElementById('confirm-time');
    if (!confirmDate || !confirmService || !confirmTime) {
        console.error('Apstiprinājuma elementi nav atrasti!');
        alert('Kļūda: Apstiprinājuma elementi nav atrasti.');
        return;
    }
    confirmDate.textContent = localStorage.getItem('selectedDate') || 'Nav izvēlēts';
    confirmService.textContent = localStorage.getItem('selectedService') || 'Nav izvēlēts';
    confirmTime.textContent = localStorage.getItem('selectedTime') || 'Nav izvēlēts';
}

function submitAnonymousBooking() {
    const name = document.getElementById('confirm-name')?.value.trim();
    const phone = document.getElementById('confirm-phone')?.value.trim();
    if (!name || !phone || !selectedDate || !selectedService || !selectedTime) {
        alert('Visi lauki ir obligāti!');
        return;
    }

    const booking = {
        name,
        phone,
        service: selectedService,
        date: selectedDate,
        time: selectedTime,
        comment: ''
    };

    fetch('/kursa-darbi/nails-booking/api/bookings/submit-anonymous-booking.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(booking)
    })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP kļūda: ${res.status} ${res.statusText}. Atbilde: ${text.substring(0, 100)}...`);
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                localStorage.removeItem('selectedDate');
                localStorage.removeItem('selectedService');
                localStorage.removeItem('selectedTime');
                nextStep('thankyou');
            } else {
                alert(data.error || 'Neizdevās veikt rezervāciju.');
            }
        })
        .catch(err => {
            console.error('Kļūda veicot rezervāciju:', err);
            alert('Neizdevās veikt rezervāciju: ' + err.message);
        });
}

function resetBooking() {
    selectedDate = null;
    selectedService = null;
    selectedTime = null;
    localStorage.removeItem('selectedDate');
    localStorage.removeItem('selectedService');
    localStorage.removeItem('selectedTime');
    nextStep('calendar');
}

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        generateCalendar(currentYear, currentMonth);
    } else {
        console.error('Kalendāra elements nav atrasts ielādes laikā!');
        alert('Kļūda: Kalendārs nav pieejams.');
    }
});