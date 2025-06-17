<?php
// File: bs/includes/image_generator.php
// Generates a placeholder image locally using PHP's GD Library.

/**
 * Creates and saves a placeholder image with text if it doesn't already exist.
 *
 * @param string $text The text to display on the image.
 * @param int $width The width of the image in pixels.
 * @param int $height The height of the image in pixels.
 * @return string The relative URL path to the image.
 */
function generatePlaceholderImage($text = 'No Image', $width = 400, $height = 400) {
    // Define the absolute path to the images directory
    $imageDir = __DIR__ . '/../images/';
    if (!is_dir($imageDir)) {
        // Create the directory if it doesn't exist
        mkdir($imageDir, 0775, true);
    }

    // Create a unique, URL-safe filename from the text to avoid recreating images
    $safe_text = preg_replace('/[^a-zA-Z0-9-]/', '_', $text);
    $filename = "placeholder_" . strtolower($safe_text) . "_{$width}x{$height}.png";
    $filepath = $imageDir . $filename;
    
    // The relative URL path for the <img> src attribute
    $fileUrl = "images/" . $filename;

    // If the image already exists, no need to generate it again.
    if (file_exists($filepath)) {
        return $fileUrl;
    }

    // --- Create the image resource ---
    $image = imagecreatetruecolor($width, $height);

    // --- Define colors ---
    $bgColor = imagecolorallocate($image, 236, 240, 241); // A light gray background (e.g., #ecf0f1)
    $textColor = imagecolorallocate($image, 52, 73, 94);   // A dark blue/gray text color (e.g., #34495e)
    $borderColor = imagecolorallocate($image, 210, 214, 222); // A subtle border color

    // Fill the background
    imagefill($image, 0, 0, $bgColor);

    // --- Add the text ---
    // Use a standard Windows font path, with a fallback for other systems
    $font_path = 'C:/Windows/Fonts/arial.ttf'; 
    if (!file_exists($font_path)) {
        $font_path = 5; // Use a built-in GD font if Arial is not found
    }

    // Calculate an appropriate font size and center the text
    $font_size = $width / 20;
    // Sanity check for font size
    if ($font_size < 10) $font_size = 10;

    $text_box = imagettfbbox($font_size, 0, $font_path, $text);
    $text_width = $text_box[2] - $text_box[0];
    $text_height = $text_box[1] - $text_box[7];
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2;

    // Draw the text on the image
    imagettftext($image, $font_size, 0, $x, $y, $textColor, $font_path, $text);

    // Draw a border around the image
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

    // Save the image as a PNG file
    imagepng($image, $filepath);

    // Free up memory
    imagedestroy($image);

    // Return the relative URL of the newly created image
    return $fileUrl;
}
?>