<?= partial('page_hero', ['kicker' => __('Erreur 404'), 'title' => __('Cette page est **introuvable**'), 'lead' => __('La page que vous cherchez a peut-être été déplacée ou n\'existe plus.')]) ?>

<section class="section">
    <div class="container center">
        <p class="hero-actions center-actions">
            <a class="btn btn-primary btn-lg" href="<?= route_url('home') ?>"><span><?= e(__('Retour à l\'accueil')) ?></span></a>
            <a class="btn btn-outline btn-lg" href="<?= route_url('projects') ?>"><span><?= e(__('Voir les projets')) ?></span></a>
        </p>
    </div>
</section>
