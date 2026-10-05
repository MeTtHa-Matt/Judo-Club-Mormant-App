(() => {
    const consentKey = 'jcm_audience_consent';
    const storageKey = 'jcm_analytics_session';
    const privacySignal = navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true;

    const script = document.currentScript;
    const endpointValue = script?.dataset.endpoint || '';
    const isAuthenticated = script?.dataset.authenticated === '1';
    if (!endpointValue) return;

    const privatePages = new Set([
        'ban.php', 'chat.php', 'gerer_index_liens.php', 'login.php', 'mailing.php', 'maintenance.php',
        'mes_enfants.php', 'profile.php', 'recup_mdp.php', 'register.php', 'reports.php', 'reset_password.php',
        'signaler.php', 'users.php', 'verify.php', 'reglement_accept.php',
    ]);
    const page = window.location.pathname.toLowerCase().split('/').pop() || 'index.php';
    if (privatePages.has(page)) return;

    let endpoint;
    try {
        endpoint = new URL(endpointValue);
    } catch {
        return;
    }
    if (window.location.protocol === 'https:' && endpoint.protocol !== 'https:') return;

    function hasConsent() {
        if (privacySignal) return false;
        try {
            return localStorage.getItem(consentKey) === 'accepted';
        } catch {
            return false;
        }
    }

    function createSessionToken() {
        try {
            const saved = sessionStorage.getItem(storageKey);
            if (/^[a-f0-9]{64}$/.test(saved || '')) return saved;
        } catch {}

        if (!window.crypto?.getRandomValues) return null;
        const bytes = new Uint8Array(32);
        window.crypto.getRandomValues(bytes);
        const token = [...bytes].map((value) => value.toString(16).padStart(2, '0')).join('');
        try { sessionStorage.setItem(storageKey, token); } catch {}
        return token;
    }

    let sessionToken = null;
    let trackingActive = false;
    let engagementSent = false;
    let engagementTimer;

    function platformType() {
        const agent = navigator.userAgent || '';
        if (/iPad|Tablet|PlayBook/i.test(agent) || (/Macintosh/i.test(agent) && navigator.maxTouchPoints > 1) || /Android/i.test(agent) && !/Mobile/i.test(agent)) return 'tablet';
        if (/Mobile|iPhone|iPod|Android/i.test(agent)) return 'mobile';
        return 'desktop';
    }

    function viewportType() {
        if (window.innerWidth < 600) return 'small';
        if (window.innerWidth < 1024) return 'medium';
        return 'large';
    }

    function referrerHost() {
        if (!document.referrer) return null;
        try {
            const referrer = new URL(document.referrer);
            if (referrer.origin === window.location.origin) return null;
            return referrer.hostname.toLowerCase().replace(/^www\./, '');
        } catch {
            return null;
        }
    }

    function send(type) {
        if (!trackingActive || !hasConsent() || !sessionToken) return;
        const body = JSON.stringify({
            type,
            is_authenticated: isAuthenticated,
            path: window.location.pathname,
            platform: platformType(),
            viewport: viewportType(),
            session_token: sessionToken,
            referrer_host: referrerHost(),
        });
        fetch(endpoint, { method: 'POST', mode: 'cors', credentials: 'omit', body, keepalive: true }).catch(() => {});
    }

    const startEngagementTimer = () => {
        window.clearTimeout(engagementTimer);
        if (trackingActive && !engagementSent && document.visibilityState === 'visible') {
            engagementTimer = window.setTimeout(() => {
                if (!trackingActive || !hasConsent()) return;
                engagementSent = true;
                send('engagement');
            }, 15000);
        }
    };
    document.addEventListener('visibilitychange', startEngagementTimer);

    function startTracking() {
        if (trackingActive || !hasConsent()) return;
        sessionToken = createSessionToken();
        if (!sessionToken) return;
        trackingActive = true;
        engagementSent = false;
        send('page_view');
        startEngagementTimer();
    }

    window.addEventListener('jcm:audience-consent', (event) => {
        if (event.detail?.choice === 'accepted') {
            startTracking();
            return;
        }
        if (event.detail?.choice === 'refused') {
            trackingActive = false;
            sessionToken = null;
            window.clearTimeout(engagementTimer);
            try { sessionStorage.removeItem(storageKey); } catch {}
        }
    });

    if (hasConsent()) startTracking();
})();