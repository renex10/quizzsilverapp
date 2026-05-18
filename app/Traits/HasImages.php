<?php

namespace App\Traits;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Trait HasImages
 *
 * Proporciona relaciones y métodos para manejar imágenes polimórficas
 * en modelos como Series, Topic, Lesson, Exam.
 *
 * CORRECCIÓN Intelephense P1014:
 * Las propiedades @property-read documentan las relaciones Eloquent
 * que se resuelven dinámicamente. Son accesibles como $model->coverImage
 * aunque estén definidas como métodos — comportamiento estándar de Eloquent.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @property-read \App\Models\Image|null                                      $coverImage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Image> $images
 * @property-read string                                                       $cover_url
 */
trait HasImages
{
    /**
     * Relación polimórfica: un modelo puede tener muchas imágenes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Relación para obtener la imagen de portada (contexto 'cover').
     * Se accede como propiedad: $model->coverImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function coverImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('context', 'cover');
    }

    /**
     * Accesor para obtener la URL de la portada directamente.
     * Uso: $model->cover_url
     *
     * @return string
     */
    public function getCoverUrlAttribute(): string
    {
        return $this->coverImage?->url ?? asset('images/default-cover.jpg');
    }

    /**
     * Reemplazar o crear la imagen de portada.
     * Elimina la imagen anterior (si existe), sube la nueva y la asocia.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string                          $altText
     * @return \App\Models\Image
     */
    public function replaceCover(UploadedFile $file, string $altText = ''): Image
    {
        $this->deleteCover();

        return app(\App\Services\ImageUploadService::class)->upload(
            $this,
            $file,
            'cover',
            $altText
        );
    }

    /**
     * Eliminar la imagen de portada actual (archivo físico y registro en DB).
     * El modelo Image tiene un listener `deleted` que también borra el archivo,
     * pero lo hacemos explícito aquí para mayor claridad.
     *
     * @return void
     */
    public function deleteCover(): void
    {
        /** @var \App\Models\Image|null $cover */
        $cover = $this->coverImage;

        if ($cover) {
            Storage::disk($cover->disk)->delete($cover->path);
            $cover->delete();
        }
    }
}