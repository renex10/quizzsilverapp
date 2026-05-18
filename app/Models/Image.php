<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = [
        'imageable_type', 'imageable_id', 'filename', 'path',
        'disk', 'mime_type', 'size', 'width', 'height',
        'context', 'alt_text', 'order'
    ];

    protected $appends = ['url'];

    /**
     * Relación polimórfica inversa.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Accesor para obtener la URL completa de la imagen.
     */
    public function getUrlAttribute(): string
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk($this->disk);
    return $disk->url($this->path);
}

    /**
     * Boot del modelo: eliminar el archivo físico cuando se borra el registro.
     */
    protected static function booted()
    {
        static::deleted(function ($image) {
            Storage::disk($image->disk)->delete($image->path);
        });
    }
}