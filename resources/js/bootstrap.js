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
    const token = localStorage.getItem('client_token') || sessionStorage.getItem('client_token') || getCookie('client_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

window.setClientToken = function (token, remember = false) {
    if (remember) {
        localStorage.setItem('client_token', token);
        sessionStorage.removeItem('client_token');
    } else {
        sessionStorage.setItem('client_token', token);
        localStorage.removeItem('client_token');
    }
};

window.clearClientToken = function () {
    sessionStorage.removeItem('client_token');
    localStorage.removeItem('client_token');
    document.cookie = 'client_token=; path=/; max-age=0';
};

window.clientLogout = function () {
    axios.post('/logout').catch(() => null).finally(() => {
        clearClientToken();
        window.location.href = '/client/login';
    });
};

