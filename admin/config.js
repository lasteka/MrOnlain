// config.js - Universāls konfigurācijas fails TIKAI ADMIN PANELIM
// Automātiski noteic servera URL un konfigurē API

// DROŠĪBAS PĀRBAUDE: Tikai admin panelim
if (!window.location.pathname.includes('/admin/')) {
    console.warn('⚠️ config.js ir paredzēts tikai admin panelim');
    // Neizpilda neko, ja nav admin direktorijā
} else {

class AdminConfig {
    constructor() {
        this.baseURL = this.detectBaseURL();
        this.apiURL = this.baseURL + 'api/admin/';
        this.adminURL = this.baseURL + 'admin/';
        
        console.log('🌐 Admin Config inicializēts:');
        console.log('  Base URL:', this.baseURL);
        console.log('  API URL:', this.apiURL);
        console.log('  Admin URL:', this.adminURL);
    }
    
    // Automātiski noteic pareizo base URL
    detectBaseURL() {
        const protocol = window.location.protocol;
        const hostname = window.location.hostname;
        const port = window.location.port;
        
        // Izveido base URL atkarībā no porta
        let baseURL;
        if (port && port !== '80' && port !== '443') {
            baseURL = `${protocol}//${hostname}:${port}/`;
        } else {
            baseURL = `${protocol}//${hostname}/`;
        }
        
        return baseURL;
    }
    
    // Iegūst API URL ar endpoint
    getAPIUrl(endpoint) {
        return this.apiURL + endpoint;
    }
    
    // Iegūst admin URL ar lapu
    getAdminUrl(page) {
        return this.adminURL + page;
    }
    
    // Iegūst pareizos headers ar token
    getHeaders(includeAuth = true) {
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (includeAuth) {
            const token = localStorage.getItem('admin_token');
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
        }
        
        return headers;
    }
    
    // Veic API izsaukumu ar pareizajiem headers
    async apiCall(endpoint, options = {}) {
        const url = this.getAPIUrl(endpoint);
        const defaultOptions = {
            headers: this.getHeaders()
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        console.log(`🌐 API Call: ${finalOptions.method || 'GET'} ${url}`);
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`❌ API Error (${response.status}):`, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            console.log(`✅ API Success:`, data);
            return data;
            
        } catch (error) {
            console.error(`❌ API Call failed:`, error);
            throw error;
        }
    }
    
    // Pārbauda vai lietotājs ir autentificēts
    isAuthenticated() {
        const token = localStorage.getItem('admin_token');
        return !!token;
    }
    
    // FIKSĒTS: Pievienojam requireAuth metodi
    requireAuth() {
        if (!this.isAuthenticated()) {
            console.log('❌ Nav autentificēts - novirza uz login');
            window.location.href = this.getAdminUrl('login.php');
            return false;
        }
        return true;
    }
    
    // FIKSĒTS: Pievienojam requireAuth metodi
    requireAuth() {
        if (!this.isAuthenticated()) {
            console.log('❌ Nav autentificēts - novirza uz login');
            window.location.href = this.getAdminUrl('login.php');
            return false;
        }
        return true;
    }
    
    // Izrakstīšanās
    logout() {
        localStorage.removeItem('admin_token');
        sessionStorage.clear();
        window.location.href = this.getAdminUrl('login.php');
    }
    
    // Debug informācija
    debug() {
        const info = {
            baseURL: this.baseURL,
            apiURL: this.apiURL,
            adminURL: this.adminURL,
            authenticated: this.isAuthenticated(),
            token: localStorage.getItem('admin_token')?.substring(0, 10) + '...',
            currentURL: window.location.href
        };
        
        console.table(info);
        return info;
    }
}

// Globālā instance (tikai admin panelim)
try {
    window.AdminConfig = new AdminConfig();
    console.log('✅ AdminConfig veiksmīgi izveidots');
} catch (error) {
    console.error('❌ AdminConfig neizdevās izveidot:', error);
}

// Pārbauda autentifikāciju lapas ielādes laikā (izņemot login lapu)
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 DOM ielādēts, pārbauda autentifikāciju...');
    
    const isLoginPage = window.location.pathname.includes('login.php');
    console.log('📍 Login page check:', isLoginPage);
    
    if (!isLoginPage) {
        // VIENKĀRŠS risinājums - tieši pārbauda localStorage
        const token = localStorage.getItem('admin_token');
        console.log('🔑 Admin token status:', token ? 'EXISTS' : 'MISSING');
        
        if (!token) {
            console.log('❌ Nav admin token - novirza uz login');
            window.location.href = '/admin/login.php';
            return;
        }
        
        console.log('✅ Token atrasts, turpina ielādi');
    }
    
    console.log('🔧 Admin Config gatavs. Pieejamās komandas:');
    console.log('  AdminConfig.debug() - debug informācija');
    console.log('  AdminConfig.apiCall("endpoint") - API izsaukums');
    console.log('  AdminConfig.logout() - izrakstīšanās');
});

} // Beidz drošības pārbaudi