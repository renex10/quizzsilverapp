<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Sube una imagen, la redimensiona si es necesario y la asocia a un modelo.
     *
     * @param Model $model      Modelo al que pertenece la imagen (debe usar HasImages)
     * @param UploadedFile $file
     * @param string $context   'cover', 'thumbnail', 'content', 'avatar'
     * @param string $altText
     * @return Image
     * @throws \Exception
     */
    public function upload($model, UploadedFile $file, string $context = 'cover', string $altText = ''): Image
    {
        // Validaciones
        $this->validateFile($file);

        // Determinar ruta de almacenamiento
        $modelClass = class_basename($model);
        $directory = "images/{$modelClass}/{$model->id}";
        
        // Generar nombre único
        $extension = $file->getClientOriginalExtension();
        $filename = "{$context}_" . time() . "_" . Str::random(8) . ".{$extension}";
        $path = "{$directory}/{$filename}";

        // Procesar y guardar
        $imageData = $this->processImage($file, $path);
        
        // Guardar registro en BD
        $image = $model->images()->create([
            'filename' => $filename,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $imageData['size'],
            'width' => $imageData['width'],
            'height' => $imageData['height'],
            'context' => $context,
            'alt_text' => $altText,
            'order' => 0,
        ]);

        return $image;
    }

    /**
     * Valida el archivo subido.
     */
    private function validateFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Formato no permitido. Use JPG, PNG o WebP.');
        }

        if ($file->getSize() > 2 * 1024 * 1024) { // 2MB
            throw new \Exception('El tamaño máximo es 2MB.');
        }
    }

    /**
     * Procesa la imagen: redimensiona y guarda en disco.
     */
    private function processImage(UploadedFile $file, string $path): array
    {
        // Obtener dimensiones originales
        list($width, $height) = getimagesize($file->getRealPath());
        $maxWidth = 1200;
        
        // Redimensionar si es necesario
        if ($width > $maxWidth) {
            $newHeight = intval(($height * $maxWidth) / $width);
            $image = $this->resizeImage($file, $maxWidth, $newHeight);
            // Guardar imagen redimensionada
            Storage::disk('public')->put($path, $image);
            $size = Storage::disk('public')->size($path);
            return ['size' => $size, 'width' => $maxWidth, 'height' => $newHeight];
        } else {
            // Guardar original
            Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));
            $size = Storage::disk('public')->size($path);
            return ['size' => $size, 'width' => $width, 'height' => $height];
        }
    }

    /**
     * Redimensiona la imagen usando GD.
     */
    private function resizeImage(UploadedFile $file, int $targetWidth, int $targetHeight): string
    {
        $source = imagecreatefromstring($file->get());
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preservar transparencia para PNG/WebP
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));
        
        ob_start();
        switch ($file->getMimeType()) {
            case 'image/jpeg':
                imagejpeg($resized);
                break;
            case 'image/png':
                imagepng($resized);
                break;
            case 'image/webp':
                imagewebp($resized);
                break;
        }
        $imageData = ob_get_clean();
        
        imagedestroy($source);
        imagedestroy($resized);
        
        return $imageData;
    }
}