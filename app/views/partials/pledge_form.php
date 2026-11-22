<?php
/**
 * Formulaire de promesse de don.
 * Variables : $action (URL), $projectOptions, $selected (slug du projet présélectionné),
 * $methods (liste des moyens de don) OU $fixedMethod (titre d'un moyen imposé).
 */
$methods ??= [];
$fixedMethod ??= null;
?>
<form class="card-form reveal" method="post" action="<?= e($action) ?>" novalidate <?= form_validate_attr() ?>>
    <?= csrf_field() ?>
    <?= form_time_field() ?>
    <div class="hp" aria-hidden="true"><label><?= e(__('Laisser ce champ vide')) ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="form-row">
        <label><?= e(__('Nom complet')) ?> *<input type="text" name="name" required minlength="2" maxlength="150" value="<?= e(old('name')) ?>" autocomplete="name"></label>
        <label><?= e(__('E-mail')) ?> *<input type="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>" autocomplete="email"></label>
    </div>
    <div class="form-row">
        <label><?= e(__('Téléphone')) ?><input type="tel" name="phone" maxlength="25" pattern="\+?[0-9][0-9 ]{5,24}" value="<?= e(old('phone')) ?>" autocomplete="tel"></label>
        <label><?= e(__('Pays')) ?><input type="text" name="country" maxlength="100" value="<?= e(old('country')) ?>" autocomplete="country-name"></label>
    </div>
    <label><?= e(__('Projet soutenu')) ?>
        <select name="project_id">
            <option value=""><?= e(__('Là où le besoin est le plus grand')) ?></option>
            <?php foreach ($projectOptions as $po): ?>
                <option value="<?= (int) $po['id'] ?>" data-currency="<?= e(project_currency($po)) ?>" <?= ($selected === $po['slug'] || old('project_id') === (string) $po['id']) ? 'selected' : '' ?>><?= e(($po['code'] ?? '') ? $po['code'] . ' — ' : '') ?><?= e(loc($po, 'title')) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="form-row">
        <label><?= e(__('Montant')) ?> (<span data-pledge-currency data-default="<?= e(setting('currency', 'Ar')) ?>"><?= e(setting('currency', 'Ar')) ?></span>)<input type="text" name="amount" inputmode="numeric" maxlength="30" pattern="[0-9 .,]+" value="<?= e(old('amount')) ?>" placeholder="<?= e(__('ex. 100 000')) ?>"></label>
        <?php if ($fixedMethod !== null): ?>
            <label><?= e(__('Moyen de don')) ?><input type="text" value="<?= e($fixedMethod) ?>" readonly><input type="hidden" name="method" value="<?= e($fixedMethod) ?>"></label>
        <?php else: ?>
            <label><?= e(__('Moyen de don')) ?>
                <select name="method">
                    <?php foreach ($methods as $m): $mt = loc($m, 'title'); ?><option <?= old('method') === $mt ? 'selected' : '' ?>><?= e($mt) ?></option><?php endforeach; ?>
                    <option <?= old('method') === __('Autre') ? 'selected' : '' ?>><?= e(__('Autre')) ?></option>
                </select>
            </label>
        <?php endif; ?>
    </div>
    <label><?= e(__('Message')) ?><textarea name="body" rows="4" maxlength="5000" placeholder="<?= e(__('Référence de transaction, mot d\'encouragement…')) ?>"><?= e(old('body')) ?></textarea></label>
    <button class="btn btn-gold btn-lg btn-block" type="submit"><span><?= icon('heart', 20) ?> <?= e(__('Envoyer ma promesse de don')) ?></span></button>
    <p class="form-note"><?= e(__('Vos données servent uniquement à vous remercier et à suivre votre don.')) ?> <a href="<?= route_url('privacy') ?>"><?= e(__('Politique de confidentialité')) ?></a></p>
</form>
<?php clear_old(); ?>
