<?= partial('page_hero', ['kicker' => __('Contact'), 'title' => __('Parlons de **votre soutien**'), 'lead' => __('Une question sur un projet, un don ou un partenariat ? Notre équipe vous répond avec plaisir.')]) ?>

<section class="section" id="formulaire">
    <div class="container split form-split">
        <div class="contact-info reveal">
            <div class="info-item"><?= icon('pin', 24) ?><div><h3><?= e(__('Adresse')) ?></h3><p><?= nl2br(e(setting('contact_address'))) ?></p></div></div>
            <?php if (setting('contact_email')): ?>
                <div class="info-item"><?= icon('mail', 24) ?><div><h3><?= e(__('E-mail')) ?></h3><p><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p></div></div>
            <?php endif; ?>
            <?php if (setting('contact_phone')): ?>
                <div class="info-item"><?= icon('phone', 24) ?><div><h3><?= e(__('Téléphone')) ?></h3><p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></p></div></div>
            <?php endif; ?>
            <?php if (setting_t('office_hours')): ?>
                <div class="info-item"><?= icon('calendar', 24) ?><div><h3><?= e(__('Horaires')) ?></h3><p><?= e(setting_t('office_hours')) ?></p></div></div>
            <?php endif; ?>
            <?php if (setting('map_embed_url') && str_starts_with(setting('map_embed_url'), 'https://')): ?>
                <iframe class="map" src="<?= e(setting('map_embed_url')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= e(__('Plan d\'accès')) ?>"></iframe>
            <?php endif; ?>
        </div>
        <form class="card-form reveal" method="post" action="<?= route_url('contact', ['hash' => 'formulaire']) ?>" novalidate <?= form_validate_attr() ?>>
            <?= csrf_field() ?>
            <?= form_time_field() ?>
            <div class="hp" aria-hidden="true"><label><?= e(__('Laisser ce champ vide')) ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form-row">
                <label><?= e(__('Nom')) ?> *<input type="text" name="name" required minlength="2" maxlength="150" value="<?= e(old('name')) ?>" autocomplete="name"></label>
                <label><?= e(__('E-mail')) ?> *<input type="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>" autocomplete="email"></label>
            </div>
            <div class="form-row">
                <label><?= e(__('Téléphone')) ?><input type="tel" name="phone" maxlength="25" pattern="\+?[0-9][0-9 ]{5,24}" value="<?= e(old('phone')) ?>" autocomplete="tel"></label>
                <label><?= e(__('Sujet')) ?><input type="text" name="subject" maxlength="255" value="<?= e(old('subject')) ?>"></label>
            </div>
            <label><?= e(__('Message')) ?> *<textarea name="body" rows="6" required minlength="5" maxlength="5000"><?= e(old('body')) ?></textarea></label>
            <button class="btn btn-primary btn-lg btn-block" type="submit"><span><?= icon('mail', 20) ?> <?= e(__('Envoyer le message')) ?></span></button>
            <p class="form-note"><?= e(__('Vos données servent uniquement à vous répondre.')) ?> <a href="<?= route_url('privacy') ?>"><?= e(__('Politique de confidentialité')) ?></a></p>
        </form>
    </div>
</section>
<?php clear_old(); ?>
