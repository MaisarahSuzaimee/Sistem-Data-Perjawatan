<?php

$src = __DIR__.'/../public/images/mystaff-logo-clean.png';
$bak = __DIR__.'/../public/images/mystaff-logo-clean.original.png';

if (! file_exists($src)) {
    fwrite(STDERR, "Missing: {$src}\n");
    exit(1);
}

if (! file_exists($bak)) {
    copy($src, $bak);
    echo "Backed up original to {$bak}\n";
}

$before = filesize($bak);
[$ow, $oh] = getimagesize($bak);

$im = imagecreatefrompng($bak);
imagesavealpha($im, true);

$w = imagesx($im);
$h = imagesy($im);
$max = 512;
$scale = min(1, $max / max($w, $h));
$nw = (int) round($w * $scale);
$nh = (int) round($h * $scale);

$out = imagecreatetruecolor($nw, $nh);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
imagealphablending($out, true);
imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
imagealphablending($out, false);
imagesavealpha($out, true);

imagepng($out, $src, 9);

imagedestroy($im);
imagedestroy($out);

clearstatcache();
$after = filesize($src);
[$nw2, $nh2] = getimagesize($src);

echo sprintf(
    "before: %d bytes (%dx%d)\nafter:  %d bytes (%dx%d)\nsaved:  %.1f%%\n",
    $before,
    $ow,
    $oh,
    $after,
    $nw2,
    $nh2,
    (1 - ($after / $before)) * 100,
);
