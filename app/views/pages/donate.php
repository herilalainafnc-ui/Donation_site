<?php
/** Formulaire de don en ligne. Variables : $providers, $projectOptions, $selected (slug du projet présélectionné). */
$freqs = enabled_frequencies();
$first = reset($providers);
$oldProvider = old('provider', $first ? $first->id() : '');
$current = $providers[$oldProvider] ?? $first;
$selectedProject = null;
foreach ($projectOptions as $po) {
    if ($selected === $po['slug'] || old('project_id') === (string) $po['id']) {
        $selectedProject = $po;
    }
}
// Devise par défaut : celle du projet choisi si la carte l'accepte, sinon la première devise de la carte.
$cardCurrencies = card_currencies();
$projIso = $selectedProject ? currency_iso(project_currency($selectedProject)) : currency_iso(setting('currency', 'Ar'));
$defaultCard = in_array($projIso, $cardCurrencies, true) ? $projIso : $cardCurrencies[0];
$currency = old('currency', $current && $current->kind() === 'mobile' ? 'MGA' : $defaultCard);
$allIso = array_values(array_unique(array_merge($cardCurrencies, ['MGA'])));
$presets = [];
$mins = [];
foreach ($allIso as $iso) {
    $presets[$iso] = preset_amounts($iso);
    $mins[$iso] = min_amount($iso);
}
$symbols = array_combine($allIso, array_map('currency_symbol', $allIso));
$oldAmount = old('amount');
$oldCustom = old('amount_custom');
$curPresets = $presets[$currency] ?? [];
if ($oldAmount === '' && $oldCustom === '' && $curPresets) {
    $oldAmount = (string) ($curPresets[min(1, count($curPresets) - 1)]);
}
$fmt = fn (float $v) => number_format($v, $v == floor($v) ? 0 : 2, lang() === 'fr' ? ',' : '.', lang() === 'fr' ? ' ' : ',');
?>
<?= partial('page_hero', ['kicker' => __('Don en ligne'), 'title' => __('Donnez en **quelques minutes**'), 'lead' => setting_t('pay_intro', __('Par carte bancaire ou Mobile Money, une seule fois ou de façon régulière : chaque don construit l\'avenir d\'un étudiant.'))]) ?>

<section class="section">
    <div class="container donate-layout">
        <form class="card-form donate-form" method="post" action="<?= e(route_url('donate')) ?>" novalidate <?= form_validate_attr() ?>
              data-donate-form data-presets="<?= e(json_encode($presets)) ?>" data-mins="<?= e(json_encode($mins)) ?>" data-symbols="<?= e(json_encode($symbols)) ?>"
              data-lang="<?= lang() ?>" data-label-once="<?= e(__('Donner :amount')) ?>" data-label-recurring="<?= e(__('Donner :amount :freq')) ?>">
            <?= csrf_field() ?>
            <?= form_time_field() ?>
            <div class="hp" aria-hidden="true"><label><?= e(__('Laisser ce champ vide')) ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <?php if (payments_test_mode()): ?>
                <p class="test-banner"><?= icon('target', 18) ?> <?= e(__('Mode test : aucun paiement réel ne sera effectué.')) ?></p>
            <?php endif; ?>

            <fieldset class="donate-step">
                <legend><span class="step-num">1</span> <?= e(__('Fréquence')) ?></legend>
                <div class="seg" role="radiogroup">
                    <?php foreach ($freqs as $f): ?>
                        <label class="seg-item"><input type="radio" name="frequency" value="<?= $f ?>" data-freq-label="<?= e(mb_strtolower(frequency_label($f))) ?>" <?= old('frequency', 'once') === $f ? 'checked' : '' ?>><span><?= e(frequency_label($f)) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="donate-step">
                <legend><span class="step-num">2</span> <?= e(__('Moyen de paiement')) ?></legend>
                <div class="pay-methods">
                    <?php foreach ($providers as $p): ?>
                        <label class="pay-method">
                            <input type="radio" name="provider" value="<?= e($p->id()) ?>" data-kind="<?= e($p->kind()) ?>" data-auto="<?= $p->supportsAutoRecurring() ? '1' : '0' ?>"
                                   data-currencies="<?= e(implode(',', $p->currencies())) ?>" data-phone-label="<?= e(__('Numéro :name', ['name' => $p->label()])) ?>" <?= $current && $current->id() === $p->id() ? 'checked' : '' ?> required>
                            <span class="pm-box">
                                <span class="pm-icon"><?= icon($p->kind() === 'card' ? 'bank' : 'mobile', 24) ?></span>
                                <span class="pm-text"><strong><?= e($p->label()) ?></strong>
                                    <small><?= e($p->kind() === 'card' ? __('Visa, Mastercard — prélèvement automatique pour un don régulier') : __('Confirmation sur votre téléphone')) ?></small></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="donate-step">
                <legend><span class="step-num">3</span> <?= e(__('Montant')) ?></legend>
                <div class="amount-head">
                    <label class="currency-field" data-currency-field><span class="label-text"><?= e(__('Devise')) ?></span>
                        <select name="currency" data-currency>
                            <?php foreach ($cardCurrencies as $iso): ?><option value="<?= $iso ?>" <?= $currency === $iso ? 'selected' : '' ?>><?= $iso ?> (<?= e(currency_symbol($iso)) ?>)</option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="presets" data-presets-box>
                    <?php foreach ($curPresets as $v): ?>
                        <label class="preset"><input type="radio" name="amount" value="<?= e((string) $v) ?>" <?= $oldCustom === '' && (float) $oldAmount === (float) $v ? 'checked' : '' ?>><span><?= e($fmt($v)) ?> <?= e(currency_symbol($currency)) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <label class="custom-amount"><span class="label-text"><?= e(__('Autre montant')) ?></span>
                    <span class="input-with-suffix"><input type="text" name="amount_custom" inputmode="decimal" pattern="[0-9 .,]+" value="<?= e($oldCustom) ?>" placeholder="<?= e(__('Votre montant')) ?>" data-custom-amount><span class="suffix" data-currency-symbol><?= e(currency_symbol($currency)) ?></span></span>
                </label>
                <p class="donate-note" data-recurring-note hidden><?= icon('mail', 16) ?> <?= e(__('Avec le Mobile Money, nous vous envoyons un e-mail à chaque échéance avec un lien pour payer en quelques secondes. Vous pouvez arrêter à tout moment.')) ?></p>
                <p class="donate-note" data-auto-note hidden><?= icon('check', 16) ?> <?= e(__('Le montant sera prélevé automatiquement à chaque échéance. Vous pouvez arrêter à tout moment grâce au lien reçu par e-mail.')) ?></p>
            </fieldset>

            <fieldset class="donate-step">
                <legend><span class="step-num">4</span> <?= e(__('Vos coordonnées')) ?></legend>
                <div class="form-row">
                    <label><span class="label-text"><?= e(__('Nom complet')) ?> *</span><input type="text" name="name" required minlength="2" maxlength="150" value="<?= e(old('name')) ?>" autocomplete="name"></label>
                    <label><span class="label-text"><?= e(__('E-mail')) ?> *</span><input type="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>" autocomplete="email"></label>
                </div>
                <div class="form-row">
                    <label data-phone-field><span class="label-text" data-phone-text><?= e(__('Téléphone')) ?></span><input type="tel" name="phone" maxlength="25" pattern="\+?[0-9][0-9 ]{5,24}" value="<?= e(old('phone')) ?>" autocomplete="tel" data-phone></label>
                    <label><span class="label-text"><?= e(__('Pays')) ?></span><input type="text" name="country" maxlength="100" value="<?= e(old('country')) ?>" autocomplete="country-name"></label>
                </div>
                <label><span class="label-text"><?= e(__('Projet soutenu')) ?></span>
                    <select name="project_id">
                        <option value=""><?= e(__('Là où le besoin est le plus grand')) ?></option>
                        <?php foreach ($projectOptions as $po): ?>
                            <option value="<?= (int) $po['id'] ?>" <?= $selectedProject && (int) $selectedProject['id'] === (int) $po['id'] ? 'selected' : '' ?>><?= e(($po['code'] ?? '') ? $po['code'] . ' — ' : '') ?><?= e(loc($po, 'title')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="label-text"><?= e(__('Message (facultatif)')) ?></span><textarea name="message" rows="3" maxlength="2000"><?= e(old('message')) ?></textarea></label>
            </fieldset>

            <button class="btn btn-gold btn-lg btn-block" type="submit"><span><?= icon('heart', 20) ?> <span data-submit-label><?= e(__('Donner')) ?></span></span></button>
            <p class="form-note"><?= icon('check', 14) ?> <?= e(__('Paiement sécurisé chez le prestataire choisi : aucune donnée bancaire ne passe par ce site.')) ?> <a href="<?= route_url('privacy') ?>"><?= e(__('Politique de confidentialité')) ?></a></p>
        </form>

        <aside class="donate-aside">
            <div class="sticky-card">
                <h3><?= e(__('Pourquoi donner en ligne ?')) ?></h3>
                <ul class="check-list">
                    <li><?= icon('check', 20) ?> <?= e(__('Paiement rapide et sécurisé')) ?></li>
                    <li><?= icon('check', 20) ?> <?= e(__('Reçu envoyé par e-mail')) ?></li>
                    <li><?= icon('check', 20) ?> <?= e(__('Don régulier modifiable ou arrêtable à tout moment')) ?></li>
                    <li><?= icon('check', 20) ?> <?= e(__('Votre don affecté au projet de votre choix')) ?></li>
                </ul>
                <p class="muted"><?= e(__('Vous préférez un virement ou un dépôt ?')) ?> <a href="<?= route_url('give') ?>"><?= e(__('Voir tous les moyens de don')) ?></a></p>
            </div>
        </aside>
    </div>
</section>
<?php clear_old(); ?>
