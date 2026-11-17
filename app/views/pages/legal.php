<?php
$text = setting_t($which === 'privacy' ? 'legal_privacy' : 'legal_terms');
$email = setting('contact_email');
// Adresse sur une ligne, sans répéter le nom de l'université déjà écrit dans le texte
$addressLines = array_filter(array_map('trim', explode("\n", setting('contact_address'))), fn($l) => $l !== '' && !str_contains($l, 'Zurcher'));
$address = implode(', ', $addressLines);
$site = (string) (parse_url((string) config('site_url', ''), PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? ''));
$html = strtr(rich_text($text), [
    '{email}' => $email !== '' ? '<a href="mailto:' . e($email) . '">' . e($email) . '</a>' : e(__('notre formulaire de contact')),
    '{address}' => e($address !== '' ? $address : 'Antsirabe, Madagascar'),
    '{site}' => '<a href="' . e(route_url('home')) . '">' . e($site) . '</a>',
]);
$updated = setting('legal_updated');
?>
<?= partial('page_hero', ['kicker' => __('Informations légales'), 'title' => $pageTitle]) ?>

<section class="section">
    <div class="container narrow legal-page">
        <?php if ($updated): ?><p class="legal-updated"><?= e(__('Dernière mise à jour : :date', ['date' => format_date($updated)])) ?></p><?php endif; ?>
        <div class="prose prose-lg"><?= $html ?></div>
        <p class="legal-switch">
            <?php if ($which === 'privacy'): ?>
                <a class="link-arrow" href="<?= e(route_url('terms')) ?>"><?= e(__('Lire les conditions générales')) ?> →</a>
            <?php else: ?>
                <a class="link-arrow" href="<?= e(route_url('privacy')) ?>"><?= e(__('Lire la politique de confidentialité')) ?> →</a>
            <?php endif; ?>
        </p>
    </div>
</section>
