document.addEventListener('DOMContentLoaded', function () {
  // Build endpoint URL using app root if provided by server (set in navbar)
  const APP_ROOT = (window.JCM && typeof window.JCM.appRoot === 'string') ? window.JCM.appRoot : '';
  const STATUS_URL = (APP_ROOT ? APP_ROOT : '') + '/includes/general/online_status.php';
  const POLL_INTERVAL = 5000; // 5 seconds

  // Update own presence via POST
  async function sendHeartbeat() {
    try {
      const res = await fetch(STATUS_URL, { method: 'POST', credentials: 'include' });
      if (!res.ok) {
        console.debug('Heartbeat POST failed', res.status);
        return false;
      }
      const data = await res.json().catch(() => null);
      if (!data || !data.success) {
        console.debug('Heartbeat response:', data);
        return false;
      }
      return true;
    } catch (e) {
      console.debug('Heartbeat error', e);
      return false;
    }
  }

  // Fetch online list and update dots
  async function fetchOnline() {
    try {
      const res = await fetch(STATUS_URL + '?_=' + Date.now(), { credentials: 'include' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.success || !Array.isArray(data.online)) return;
      const onlineSet = new Set(data.online.map(id => String(id)));
      document.querySelectorAll('.user-status-dot').forEach((dot) => {
        const uid = dot.getAttribute('data-user-id');
        if (onlineSet.has(uid)) {
          dot.classList.add('online');
          dot.classList.remove('offline');
        } else {
          dot.classList.remove('online');
          dot.classList.add('offline');
        }
      });
    } catch (e) {
      console.debug('fetchOnline error', e);
    }
  }

  // Keep presence alive while page is visible/active
  let pollTimer = null;
  function startPolling() {
    // initial ping and fetch (ensure heartbeat completes before fetching online list)
    const doCycle = async () => {
      await sendHeartbeat();
      await fetchOnline();
    };
    doCycle();
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(doCycle, POLL_INTERVAL);
  }
  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  // Pause heartbeats when page not visible (browser tab inactive) to allow server inactivity detection
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') startPolling(); else stopPolling();
  });

  // Send a final beacon on unload so the server can mark offline quickly
  window.addEventListener('unload', function () {
    try {
      navigator.sendBeacon(STATUS_URL, new URLSearchParams([['_unload', '1']]));
    } catch (e) {
      // ignore
    }
  });

  // Start polling initially
  startPolling();
});
