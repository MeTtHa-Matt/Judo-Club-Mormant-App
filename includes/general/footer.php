<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <a href="index.php" class="footer-logo">Judo Club de Mormant</a>
                <p class="footer-text">Passion, respect et excellence. Rejoignez notre club et progressez dans un esprit
                    de famille.</p>
            </div>
            <div class="col-md-3">
                <h3>Contact</h3>
                <ul class="footer-contact list-unstyled">
                    <li><strong>Dojo Teddy Riner</strong></li>
                    <li>77720 Mormant</li>
                    <li><a href="mailto:mormantjudoclub@gmail.com">mormantjudoclub@gmail.com</a></li>
                    <li><a href="tel:+33640670877">06 40 67 08 77</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h3>Suivez-nous</h3>
                <ul class="footer-social list-unstyled d-flex flex-column gap-2">
                    <li><a href="https://www.facebook.com/JCMormant" aria-label="Facebook"><i class="bi bi-facebook"></i> Facebook</a></li>
                    <li><a href="https://www.instagram.com/judoclubmormant/" aria-label="Instagram"><i class="bi bi-instagram"></i> Instagram</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom text-center mt-4 pt-4 border-top">
            <p class="mb-0 ">Site réalisé par <a href="fruit_ninja.php" style="text-decoration:none; color:white;">Matthew</a></p>
            <p class="mb-0 mt-2">© <?= date('Y') ?> Judo Club de Mormant. Tous droits réservés.</p>
            <button type="button" class="jcm-audience-preferences" id="jcm-audience-preferences">Préférences de mesure d’audience</button>
        </div>
    </div>
</footer>

<button type="button" class="report-launcher" data-bs-toggle="modal" data-bs-target="#reportHelpModal"
    aria-label="Signaler un problème" title="Signaler un problème">
    <i class="bi bi-flag-fill" aria-hidden="true"></i>
</button>

<div class="modal fade" id="reportHelpModal" tabindex="-1" aria-labelledby="reportHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content report-help-modal">
            <div class="modal-header border-0">
                <div class="report-help-icon"><i class="bi bi-life-preserver"></i></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-0">
                <h2 class="h4 fw-bold" id="reportHelpModalLabel">Vous avez un problème ?</h2>
                <p class="text-muted mb-4">Signalez-le à l’équipe du Judo Club de Mormant.</p>
                <a class="btn btn-judo-red w-100" href="signaler.php"><i class="bi bi-flag-fill me-2"></i>Signalez-le</a>
            </div>
        </div>
    </div>
</div>

<aside class="jcm-consent-banner" id="jcm-consent-banner" aria-labelledby="jcm-consent-title" aria-describedby="jcm-consent-description" hidden>
    <div class="jcm-consent-copy">
        <p class="jcm-consent-kicker">VOTRE CHOIX</p>
        <h2 id="jcm-consent-title" tabindex="-1">Autorisez-vous les statistiques du site ?</h2>
        <p id="jcm-consent-description">Avec votre accord, nous mesurerons les pages consultées, le type d’appareil, le système, le navigateur et le domaine d’origine pour améliorer le site. Une session pseudonyme est utilisée; aucune adresse IP ni recherche saisie n’est enregistrée. Votre choix peut être modifié depuis le pied de page.</p>
    </div>
    <div class="jcm-consent-actions">
        <button type="button" class="jcm-consent-button jcm-consent-decline" id="jcm-consent-decline">Refuser</button>
        <button type="button" class="jcm-consent-button jcm-consent-accept" id="jcm-consent-accept">Autoriser les statistiques</button>
    </div>
</aside>

<script src="js/footer.js?v=<?php echo filemtime('js/footer.js'); ?>"></script>
<script src="js/analytics-consent.js?v=1" defer></script>
<script src="js/judo-analytics.js?v=3" data-endpoint="https://dashboard.judo-club-mormant.fr/track.php" defer></script>
