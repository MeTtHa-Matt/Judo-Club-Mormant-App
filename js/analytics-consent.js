(() => {
    const consentKey = 'jcm_audience_consent';
    const banner = document.getElementById('jcm-consent-banner');
    const acceptButton = document.getElementById('jcm-consent-accept');
    const declineButton = document.getElementById('jcm-consent-decline');
    const preferencesButton = document.getElementById('jcm-audience-preferences');
    if (!banner || !acceptButton || !declineButton || !preferencesButton) return;
    if (navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true) {
        banner.hidden = true;
        preferencesButton.hidden = true;
        return;
    }

    function getChoice() {
        try {
            const choice = localStorage.getItem(consentKey);
            return choice === 'accepted' || choice === 'refused' ? choice : null;
        } catch {
            return null;
        }
    }

    function dispatchChoice(choice) {
        window.dispatchEvent(new CustomEvent('jcm:audience-consent', { detail: { choice } }));
    }

    function setChoice(choice) {
        try {
            localStorage.setItem(consentKey, choice);
        } catch {
            banner.hidden = false;
            return;
        }
        banner.hidden = true;
        dispatchChoice(choice);
    }

    const currentChoice = getChoice();
    banner.hidden = currentChoice !== null;
    if (currentChoice) dispatchChoice(currentChoice);

    acceptButton.addEventListener('click', () => setChoice('accepted'));
    declineButton.addEventListener('click', () => setChoice('refused'));
    preferencesButton.addEventListener('click', () => {
        banner.hidden = false;
        banner.querySelector('h2')?.focus();
    });
})();