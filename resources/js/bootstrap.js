import axios from 'axios';
window.axios = axios;

axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
}

axios.interceptors.request.use(config => {
    const token = sessionStorage.getItem('client_token') || getCookie('client_token');
    console.log('[axios] attach token', token);

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

window.setClientToken = function (token) {
    console.log('[client-token] setClientToken', token);
    sessionStorage.setItem('client_token', token);
    // Keep API token in sessionStorage for Authorization header.
    // The web auth cookie is issued by the backend /api/login response.
    console.log('[client-token] cookie after login response', document.cookie);
};

window.clearClientToken = function () {
    sessionStorage.removeItem('client_token');
    document.cookie = 'client_token=; path=/; max-age=0';
};

window.clientLogout = function () {
    axios.post('/logout').catch(() => null).finally(() => {
        clearClientToken();
        window.location.href = '/login';
    });
};

