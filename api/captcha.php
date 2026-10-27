<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate 5-character random alphanumeric string (excluding confusing characters)
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$code = '';
for ($i = 0; $i < 5; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

$_SESSION['captcha_code'] = strtolower($code);

// Disable caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if GD extension is available
if (extension_loaded('gd') && function_exists('imagecreate')) {
    header('Content-Type: image/png');

    $width = 130;
    $height = 42;
    $image = imagecreate($width, $height);

    $bg = imagecolorallocate($image, 245, 247, 250);
    $text_color = imagecolorallocate($image, 15, 23, 42);
    $line_color = imagecolorallocate($image, 239, 68, 68);
    $noise_color = imagecolorallocate($image, 148, 163, 184);

    // Add noise dots
    for ($i = 0; $i < 60; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
    }

    // Add random noise lines
    for ($i = 0; $i < 4; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }

    // Write text
    $font_size = 5;
    $x = 20;
    for ($i = 0; $i < 5; $i++) {
        $y = rand(8, 14);
        imagechar($image, $font_size, $x, $y, $code[$i], $text_color);
        $x += 20;
    }

    imagepng($image);
    imagedestroy($image);
    exit;
} else {
    // Dynamic SVG CAPTCHA Fallback
    header('Content-Type: image/svg+xml');
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="42" viewBox="0 0 130 42">';
    $svg .= '<rect width="130" height="42" fill="#f8fafc" rx="8" stroke="#cbd5e1" stroke-width="1.5"/>';
    
    // Background noise lines
    $svg .= '<line x1="10" y1="30" x2="120" y2="10" stroke="#ef4444" stroke-width="1.5" opacity="0.6"/>';
    $svg .= '<line x1="5" y1="12" x2="125" y2="35" stroke="#3b82f6" stroke-width="1.5" opacity="0.5"/>';

    $x = 18;
    for ($i = 0; $i < 5; $i++) {
        $rotate = rand(-15, 15);
        $y = rand(26, 32);
        $svg .= "<text x=\"$x\" y=\"$y\" font-family=\"monospace\" font-size=\"22\" font-weight=\"bold\" fill=\"#0f172a\" transform=\"rotate($rotate, $x, $y)\">{$code[$i]}</text>";
        $x += 22;
    }
    
    $svg .= '</svg>';
    echo $svg;
    exit;
}
