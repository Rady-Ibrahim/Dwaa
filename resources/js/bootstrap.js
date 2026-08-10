import axios from 'axios';
window.axios = axios;

axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const TOKEN_COOKIE_DAYS = 14;

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
}

function getClientToken() {
    return localStorage.getItem('client_token')
        || sessionStorage.getItem('client_token')
        || getCookie('client_token')
        || null;
}

function writeClientCookie(token) {
    const expires = new Date(Date.now() + TOKEN_COOKIE_DAYS * 24 * 60 * 60 * 1000).toUTCString();
    document.cookie = `client_token=${encodeURIComponent(token)}; path=/; expires=${expires}; SameSite=Lax`;
}

window.setClientToken = function (token, remember = false) {
    if (remember) {
        localStorage.setItem('client_token', token);
        sessionStorage.removeItem('client_token');
    } else {
        sessionStorage.setItem('client_token', token);
        localStorage.removeItem('client_token');
    }
    writeClientCookie(token);
};

window.clearClientToken = function () {
    sessionStorage.removeItem('client_token');
    localStorage.removeItem('client_token');
    document.cookie = 'client_token=; path=/; max-age=0';
};

window.redirectToLogin = function (reason = 'expired') {
    clearClientToken();
    window.location.replace(`/client/login?${reason}=1`);
};

// ── تجديد التوكن تلقائياً (Silent Refresh) ──────────────────────────────
let refreshPromise = null;

async function tryRefreshToken() {
    if (refreshPromise) return refreshPromise;

    refreshPromise = (async () => {
        const token = getClientToken();
        if (!token) throw new Error('no-token');

        const res = await axios.post('/refresh', {}, {
            headers: { Authorization: `Bearer ${token}` },
        });

        const newToken = res.data?.token;
        if (!newToken) throw new Error('empty-token');

        const remember = !!localStorage.getItem('client_token');
        setClientToken(newToken, remember);
        return newToken;
    })().finally(() => {
        refreshPromise = null;
    });

    return refreshPromise;
}

function isRefreshRequest(config) {
    return !!config && String(config.url).endsWith('/refresh');
}

axios.interceptors.request.use(config => {
    const token = getClientToken();
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

axios.interceptors.response.use(
    res => res,
    async error => {
        const { response, config } = error;
        const status = response?.status;

        // تجديد تلقائي عند 401 — مرة واحدة فقط لكل طلب (لا يدخل في حلقة مع /refresh)
        if (status === 401 && config && !config._retried && !isRefreshRequest(config)) {
            config._retried = true;
            try {
                await tryRefreshToken();
                return axios(config); // إعادة تنفيذ الطلب الأصلي بالتوكن الجديد
            } catch (refreshErr) {
                redirectToLogin('expired');
                return Promise.reject(refreshErr);
            }
        }

        if (status === 402) {
            redirectToLogin('subscription');
        } else if (status === 403) {
            redirectToLogin('blocked');
        } else if (status === 419) {
            redirectToLogin('expired');
        }

        return Promise.reject(error);
    }
);

// تجديد استباقي في الخلفية كل 30 دقيقة أثناء فتح الصفحة
setInterval(async () => {
    if (getClientToken() && !document.hidden) {
        try {
            await tryRefreshToken();
        } catch (e) { /* سيُعامل عند أي طلب لاحق */ }
    }
}, 30 * 60 * 1000);

window.clientLogout = function () {
    axios.post('/logout').catch(() => null).finally(() => {
        clearClientToken();
        window.location.href = '/client/login';
    });
};
