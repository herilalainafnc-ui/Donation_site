<?= partial('page_hero', ['kicker' => __('Comment donner'), 'title' => __('Votre générosité **change des vies**'), 'lead' => setting_t('give_intro')]) ?>

<section class="section">
    <div class="container">
        <?php if (setting('online_donation_url')): ?>
            <div class="online-give reveal">
                <div><?= icon('globe', 32) ?><div><h2><?= e(__('Don en ligne sécurisé')) ?></h2><p><?= e(__('Donnez en quelques clics par carte bancaire, où que vous soyez.')) ?></p></div></div>
                <a class="btn btn-gold btn-lg" href="<?= e(setting('online_donation_url')) ?>" target="_blank" rel="noopener"><span><?= e(setting_t('online_donation_label', __('Donner en ligne'))) ?></span></a>
            </div>
        <?php endif; ?>

        <div class="methods-grid">
            <?php foreach ($methods as $m): $details = loc($m, 'details'); ?>
                <article class="method-card reveal">
                    <span class="method-icon"><?= icon($m['icon'], 30) ?></span>
                    <h3><?= e(loc($m, 'title')) ?></h3>
                    <?php if (loc($m, 'description') !== ''): ?><p><?= e(loc($m, 'description')) ?></p><?php endif; ?>
                    <?php if (trim($details) !== ''): ?>
                        <dl class="details">
                            <?php foreach (preg_split('/\R/', trim($details)) as $line):
                                if (trim($line) === '') continue;
                                [$k, $v] = str_contains($line, ':') ? array_map('trim', explode(':', $line, 2)) : ['', trim($line)]; ?>
                                <div>
                                    <?php if ($k !== ''): ?><dt><?= e($k) ?></dt><?php endif; ?>
                                    <dd><span><?= e($v) ?></span>
                                        <button type="button" class="copy-btn" data-copy="<?= e($v) ?>" aria-label="<?= e(__('Copier')) ?> <?= e($k ?: $v) ?>"><?= icon('copy', 16) ?></button>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="promesse">
    <div class="container split form-split">
        <div class="reveal">
            <p class="kicker"><?= e(__('Formulaire de don')) ?></p>
            <h2 class="section-title"><?= e(__('Faites-nous part de votre don')) ?></h2>
            <p><?= e(setting_t('give_after')) ?></p>
            <ul class="check-list">
                <li><?= icon('check', 20) ?> <?= e(__('Un remerciement personnel')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Un reçu sur demande')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Des nouvelles de l\'avancement du projet')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Votre nom sur le mur des donateurs (si vous le souhaitez)')) ?></li>
            </ul>
        </div>
        <form class="card-form reveal" method="post" action="<?= route_url('give', ['hash' => 'promesse']) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="hp" aria-hidden="true"><label><?= e(__('Laisser ce champ vide')) ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form-row">
                <label><?= e(__('Nom complet')) ?> *<input type="text" name="name" required maxlength="150" value="<?= e(old('name')) ?>" autocomplete="name"></label>
                <label><?= e(__('E-mail')) ?> *<input type="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>" autocomplete="email"></label>
            </div>
            <div class="form-row">
                <label><?= e(__('Téléphone')) ?><input type="tel" name="phone" maxlength="50" value="<?= e(old('phone')) ?>" autocomplete="tel"></label>
                <label><?= e(__('Pays')) ?><input type="text" name="country" maxlength="100" value="<?= e(old('country')) ?>" autocomplete="country-name"></label>
            </div>
            <label><?= e(__('Projet soutenu')) ?>
                <select name="project_id">
                    <option value=""><?= e(__('Là où le besoin est le plus grand')) ?></option>
                    <?php foreach ($projectOptions as $po): ?>
                        <option value="<?= (int) $po['id'] ?>" <?= ($selected === $po['slug'] || old('project_id') === (string) $po['id']) ? 'selected' : '' ?>><?= e(loc($po, 'title')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-row">
                <label><?= e(__('Montant')) ?> (<?= e(setting('currency', 'Ar')) ?>)<input type="text" name="amount" inputmode="numeric" maxlength="30" value="<?= e(old('amount')) ?>" placeholder="<?= e(__('ex. 100 000')) ?>"></label>
                <label><?= e(__('Moyen de don')) ?>
                    <select name="method">
                        <?php foreach ($methods as $m): $mt = loc($m, 'title'); ?><option <?= old('method') === $mt ? 'selected' : '' ?>><?= e($mt) ?></option><?php endforeach; ?>
                        <option <?= old('method') === __('Autre') ? 'selected' : '' ?>><?= e(__('Autre')) ?></option>
                    </select>
                </label>
            </div>
            <label><?= e(__('Message')) ?><textarea name="body" rows="4" maxlength="5000" placeholder="<?= e(__('Référence de transaction, mot d\'encouragement…')) ?>"><?= e(old('body')) ?></textarea></label>
            <button class="btn btn-gold btn-lg btn-block" type="submit"><span><?= icon('heart', 20) ?> <?= e(__('Envoyer ma promesse de don')) ?></span></button>
            <p class="form-note"><?= e(__('Vos données servent uniquement à vous remercier et à suivre votre don.')) ?></p>
        </form>
    </div>
</section>
<?php clear_old(); ?>
