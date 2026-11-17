<?php
/**
 * Optimise les images déjà téléversées (public/uploads) : largeur maximale, qualité web, EXIF supprimés.
 * Une image n'est remplacée que si la nouvelle version est plus légère.
 *
 *   php install/optimize_images.php            (aperçu, rien n'est modifié)
 *   php install/optimize_images.php --apply    (optimise réellement)
 *
 * Faites une sauvegarde de public/uploads avant d'utiliser --apply.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('À lancer en ligne de commande uniquement.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$apply = in_array('--apply', $argv, true);
$before = $after = $count = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUBLIC_DIR . '/uploads', FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $path = $file->getPathname();
    if (!preg_match('/\.(jpe?g|png|webp)$/i', $path) || !($info = @getimagesize($path))) {
        continue;
    }
    $size = filesize($path);
    $work = $apply ? $path : sys_get_temp_dir() . '/uaz-opt-' . md5($path) . '.' . pathinfo($path, PATHINFO_EXTENSION);
    if (!$apply) {
        copy($path, $work);
    }
    resize_image($work, $info);
    clearstatcache(true, $work);
    $new = filesize($work);
    if (!$apply) {
        unlink($work);
    }
    $before += $size;
    $after += $new;
    $count++;
    printf("%-70s %4d Ko → %4d Ko  (%d × %d)\n", substr($path, strlen(PUBLIC_DIR) + 1), $size / 1024, $new / 1024, $info[0], $info[1]);
}
printf("\n%d image(s) : %d Ko → %d Ko (%s)\n", $count, $before / 1024, $after / 1024, $apply ? 'appliqué' : 'aperçu, relancez avec --apply');
