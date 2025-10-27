<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        // Create covers directory
        $coversPath = storage_path('app/private/covers');
        if (!file_exists($coversPath)) {
            mkdir($coversPath, 0755, true);
        }

        // Create public images directory
        $imagesPath = public_path('images');
        if (!file_exists($imagesPath)) {
            mkdir($imagesPath, 0755, true);
        }

        // Create default document cover image
        $this->createDefaultDocumentCover();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('covers');
    }

    private function createDefaultDocumentCover()
    {
        $imagePath = public_path('images/default-document-cover.jpg');
        
        if (file_exists($imagePath)) {
            return; // Already exists
        }

        $image = imagecreate(300, 400);
        $backgroundColor = imagecolorallocate($image, 240, 248, 255);
        $textColor = imagecolorallocate($image, 30, 64, 175);
        $borderColor = imagecolorallocate($image, 59, 130, 246);

        imagefill($image, 0, 0, $backgroundColor);
        imagerectangle($image, 0, 0, 299, 399, $borderColor);
        imagerectangle($image, 5, 5, 294, 394, $borderColor);

        // Add document icon
        $iconY = 120;
        imagefilledrectangle($image, 120, $iconY, 180, $iconY + 60, $textColor);
        imagefilledrectangle($image, 130, $iconY + 10, 170, $iconY + 50, $backgroundColor);

        // Add text
        imagestring($image, 4, 90, 200, 'DOKUMEN', $textColor);
        imagestring($image, 4, 85, 220, 'STATISTIK', $textColor);
        imagestring($image, 3, 130, 280, 'BPS', $textColor);
        imagestring($image, 2, 95, 300, 'SULAWESI UTARA', $textColor);

        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);
    }
};
