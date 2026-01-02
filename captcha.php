<?php
session_start();

// Generate random captcha code
$captcha_code = sprintf('%03d', rand(100, 999));
$_SESSION['captcha'] = $captcha_code;

// Create image
$width = 150;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Enable antialiasing
imageantialias($image, true);

// Colors
$bg_color = imagecolorallocate($image, 255, 111, 0); // #ff6f00
$text_color = imagecolorallocate($image, 251, 251, 251); // #fbfbfb
$noise_color = imagecolorallocate($image, 230, 99, 0);

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add subtle gradient
for($y = 0; $y < $height; $y += 4) {
    imageline($image, 0, $y, $width, $y, imagecolorallocate($image, 255, 120, 10));
}

// Add minimal noise
for($i = 0; $i < 40; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Use imagettftext with a downloaded font file
// Download a font like Arial Bold and place it in the same directory
$font_file = __DIR__ . '/arial-bold.ttf';

// If you don't have a TTF file, you can use system fonts:
$system_fonts = [
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/arial.ttf'
];

// Find available font
if(!file_exists($font_file)) {
    foreach($system_fonts as $font) {
        if(file_exists($font)) {
            $font_file = $font;
            break;
        }
    }
}

if(file_exists($font_file)) {
    // Use TTF for large, crisp text
    $font_size = 28; // Large font size
    
    $bbox = imagettfbbox($font_size, 0, $font_file, $captcha_code);
    $text_width = abs($bbox[4] - $bbox[0]);
    $text_height = abs($bbox[5] - $bbox[1]);
    
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2;
    
    // Shadow
    imagettftext($image, $font_size, 0, $x + 2, $y + 2, 
                 imagecolorallocate($image, 180, 70, 0), $font_file, $captcha_code);
    
    // Main text
    imagettftext($image, $font_size, 0, $x, $y, $text_color, $font_file, $captcha_code);
} else {
    // Fallback to large custom text
    $font_size = 4;
    // Double the size by drawing 2x2 blocks for each pixel
    $chars = str_split($captcha_code);
    $spacing = 25;
    $start_x = ($width - (count($chars) * $spacing)) / 2 + 10;
    
    foreach($chars as $i => $char) {
        $x = $start_x + ($i * $spacing);
        $y = 15;
        
        // Draw character 4 times in a 2x2 grid to make it appear larger
        for($dx = 0; $dx <= 2; $dx++) {
            for($dy = 0; $dy <= 2; $dy++) {
                imagestring($image, $font_size, $x + $dx, $y + $dy, $char, $text_color);
            }
        }
    }
}

// Add security lines
for($i = 0; $i < 2; $i++) {
    imageline($image, rand(0, 30), rand(0, $height), 
              rand($width-30, $width), rand(0, $height), 
              imagecolorallocate($image, 230, 99, 0));
}

// Output as PNG
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>