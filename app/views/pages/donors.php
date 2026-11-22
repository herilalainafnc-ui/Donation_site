<?= partial('page_hero', ['kicker' => __('Nos donateurs'), 'title' => __('Merci **du fond du cœur**'), 'lead' => __('Particuliers, églises, entreprises et organisations : chacun de ces noms représente un pas de plus vers l\'avenir de nos étudiants.')]) ?>

<section class="section">
    <div class="container">
        <?php if ($total): ?>
            <?php foreach (DONOR_TYPES_PLURAL as $type => $label): if (empty($groups[$type])) continue; ?>
                <div class="donor-group">
                    <h2 class="section-title small"><?= e(__($label)) ?> <span class="count"><?= count($groups[$type]) ?></span></h2>
                    <div class="donor-grid">
                        <?php foreach ($groups[$type] as $d): ?><?= partial('donor_card', ['d' => $d, 'full' => true]) ?><?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state"><?= icon('users', 48) ?><p><?= e(__('Soyez le premier à rejoindre le mur des donateurs !')) ?></p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-alt">
    <div class="container narrow center reveal">
        <h2 class="section-title"><?= e(__('Vous aussi, rejoignez-les')) ?></h2>
        <p><?= e(__('Votre nom (ou celui de votre église, entreprise ou organisation) peut figurer ici. Vous pouvez aussi choisir de rester anonyme.')) ?></p>
        <a class="btn btn-gold btn-lg" href="<?= e(give_url()) ?>"><span><?= icon('heart', 20) ?> <?= e(__('Faire un don')) ?></span></a>
    </div>
</section>
