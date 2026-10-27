<?php if (!empty($familyIncomingRequest)): ?>
    <div class="modal fade" id="familyMatchModal" tabindex="-1" aria-labelledby="familyMatchModalTitle"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title fs-5 fw-bold" id="familyMatchModalTitle">
                        <i class="bi bi-people-fill text-judo-red me-2"></i>Demande de rattachement familial
                    </h2>
                </div>
                <div class="modal-body">
                    <p>
                        <strong><?= htmlspecialchars($familyIncomingRequest['parent_firstname'] . ' ' . $familyIncomingRequest['parent_lastname']) ?></strong>
                        (<?= htmlspecialchars($familyIncomingRequest['parent_email']) ?>) souhaite vous ajouter comme enfant à sa famille.
                    </p>
                    <p class="mb-0">Ce compte vous a ajouté comme enfant. Est-ce bien votre parent ? Si vous acceptez, vous rejoindrez sa famille et ses membres pourront voir vos inscriptions aux compétitions.</p>
                </div>
                <div class="modal-footer border-0 pt-0 flex-column flex-sm-row">
                    <form method="post" action="includes/account/family_process.php"
                        class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="respond_child_family_request">
                        <input type="hidden" name="request_id" value="<?= (int) $familyIncomingRequest['id'] ?>">
                        <button type="submit" name="decision" value="decline" class="btn btn-outline-secondary flex-fill">
                            Non
                        </button>
                        <button type="submit" name="decision" value="accept" class="btn btn-judo-red flex-fill">
                            Oui, c’est mon parent
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif (!empty($familyMatchCandidate)): ?>
    <div class="modal fade" id="familyMatchModal" tabindex="-1" aria-labelledby="familyMatchModalTitle"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title fs-5 fw-bold" id="familyMatchModalTitle">
                        <i class="bi bi-person-check-fill text-judo-red me-2"></i>Compte détecté
                    </h2>
                </div>
                <div class="modal-body">
                    <p>Un compte correspondant au profil de l’enfant a été détecté. S’agit-il de votre enfant ? Voulez-vous l’ajouter à votre famille ?</p>
                    <div class="rounded-3 bg-light p-3">
                        <div class="fw-semibold">
                            <?= htmlspecialchars($familyMatchCandidate['account_firstname'] . ' ' . $familyMatchCandidate['account_lastname']) ?>
                        </div>
                        <div class="text-break text-muted"><?= htmlspecialchars($familyMatchCandidate['email']) ?></div>
                    </div>
                    <p class="mt-3 mb-0">Profil enfant : <strong><?= htmlspecialchars($familyMatchCandidate['child_firstname'] . ' ' . $familyMatchCandidate['child_lastname']) ?></strong></p>
                </div>
                <div class="modal-footer border-0 pt-0 flex-column flex-sm-row">
                    <form method="post" action="includes/account/family_process.php"
                        class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="child_id" value="<?= (int) $familyMatchCandidate['child_id'] ?>">
                        <input type="hidden" name="target_account_id" value="<?= (int) $familyMatchCandidate['account_id'] ?>">
                        <button type="submit" name="action" value="dismiss_child_account_match"
                            class="btn btn-outline-secondary flex-fill">Non</button>
                        <button type="submit" name="action" value="send_child_family_request"
                            class="btn btn-judo-red flex-fill">Oui, envoyer une demande</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
