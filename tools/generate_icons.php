<?php

function createPwaIcon($size, $outputPath, $isMaskable = false) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Color definitions
    $bgDark = imagecolorallocate($img, 15, 23, 42); // #0F172A (slate-900)
    $borderEmerald = imagecolorallocate($img, 16, 185, 129); // #10B981 (emerald-500)
    $accentAmber = imagecolorallocate($img, 245, 158, 11); // #F59E0B (amber-500)
    $cardBg = imagecolorallocate($img, 30, 41, 59); // #1E293B (slate-800)
    $white = imagecolorallocate($img, 255, 255, 255);
    $glowGreen = imagecolorallocatealpha($img, 16, 185, 129, 90);

    // Fill entire background
    imagefill($img, 0, 0, $bgDark);

    $scale = $size / 512.0;

    // Outer glow / card
    $margin = $isMaskable ? (int)(72 * $scale) : (int)(32 * $scale);
    $cardRadius = (int)(64 * $scale);
    $w = $size - 2 * $margin;
    $h = $size - 2 * $margin;

    // Draw card background
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $w, $h, $cardBg);

    // Draw emerald outer ring
    imagesetthickness($img, (int)(8 * $scale));
    imageellipse($img, (int)($size / 2), (int)($size / 2), $w, $h, $borderEmerald);

    // Draw inner amber accent ring
    imagesetthickness($img, (int)(3 * $scale));
    imageellipse($img, (int)($size / 2), (int)($size / 2), (int)($w - 24 * $scale), (int)($h - 24 * $scale), $accentAmber);

    // Draw wallet / bag / monogram emblem in center
    // Let's draw high-contrast "DF" typography and "Rp" badge
    $centerX = (int)($size / 2);
    $centerY = (int)($size / 2);

    // Pulse dot at top right of the badge
    $pulseX = (int)($centerX + 80 * $scale);
    $pulseY = (int)($centerY - 80 * $scale);
    $pulseRadius = (int)(24 * $scale);
    imagefilledellipse($img, $pulseX, $pulseY, $pulseRadius * 2, $pulseRadius * 2, $glowGreen);
    imagefilledellipse($img, $pulseX, $pulseY, (int)($pulseRadius * 1.2), (int)($pulseRadius * 1.2), $borderEmerald);

    // Main Letters "D" and "F"
    // Use filled polygons / thick lines to form modern geometric monogram
    imagesetthickness($img, (int)(22 * $scale));

    // Letter 'D'
    $dLeft = (int)($centerX - 120 * $scale);
    $dTop = (int)($centerY - 80 * $scale);
    $dBottom = (int)($centerY + 80 * $scale);
    $dCurveX = (int)($centerX - 35 * $scale);

    imageline($img, $dLeft, $dTop, $dLeft, $dBottom, $white);
    imageline($img, $dLeft, $dTop, $dCurveX - (int)(15 * $scale), $dTop, $white);
    imageline($img, $dLeft, $dBottom, $dCurveX - (int)(15 * $scale), $dBottom, $white);
    imagearc($img, $dCurveX - (int)(15 * $scale), $centerY, (int)(75 * $scale), (int)(160 * $scale), 270, 90, $white);

    // Letter 'F' in Emerald / Amber
    $fLeft = (int)($centerX + 25 * $scale);
    $fTop = (int)($centerY - 80 * $scale);
    $fBottom = (int)($centerY + 80 * $scale);
    $fRight = (int)($centerX + 115 * $scale);
    $fMid = (int)($centerY - 5 * $scale);
    $fMidRight = (int)($centerX + 90 * $scale);

    imageline($img, $fLeft, $fTop, $fLeft, $fBottom, $borderEmerald);
    imageline($img, $fLeft, $fTop, $fRight, $fTop, $borderEmerald);
    imageline($img, $fLeft, $fMid, $fMidRight, $fMid, $accentAmber);

    // Small subtext pill "MALANG" or "DRIVER"
    $pillY = (int)($centerY + 120 * $scale);
    $pillW = (int)(140 * $scale);
    $pillH = (int)(24 * $scale);
    imagefilledrectangle($img, $centerX - (int)($pillW / 2), $pillY - (int)($pillH / 2), $centerX + (int)($pillW / 2), $pillY + (int)($pillH / 2), $cardBg);
    imagesetthickness($img, (int)(2 * $scale));
    imagerectangle($img, $centerX - (int)($pillW / 2), $pillY - (int)($pillH / 2), $centerX + (int)($pillW / 2), $pillY + (int)($pillH / 2), $borderEmerald);

    // Output to PNG
    imagepng($img, $outputPath, 9);
    imagedestroy($img);
    echo "Generated $outputPath ({$size}x{$size}, maskable=" . ($isMaskable ? 'yes' : 'no') . ")\n";
}

$outputDir = __DIR__ . '/../public/icons';
createPwaIcon(192, $outputDir . '/icon-192x192.png', false);
createPwaIcon(512, $outputDir . '/icon-512x512.png', false);
createPwaIcon(512, $outputDir . '/icon-512x512-maskable.png', true);
createPwaIcon(180, $outputDir . '/apple-touch-icon.png', false);
