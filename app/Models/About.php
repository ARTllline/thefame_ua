<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class About extends Model implements HasMedia
{
    use HasFactory, HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'text_ua',
        'text_dubai',
        'accent_ua',
        'accent_dubai',
        'label_dubai',
        'label_ua',
    ];

    public $translatable = [
        'text_ua',
        'text_dubai',
        'accent_ua',
        'accent_dubai',
        'label_dubai',
        'label_ua',

    ];

    protected $casts = [
        'text_ua' => 'array',
        'text_dubai' => 'array',
        'accent_ua' => 'array',
        'accent_dubai' => 'array',
        'label_dubai' => 'array',
        'label_ua' => 'array',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('webp')
            ->width(1280)
            ->height(1280)
            ->format(Manipulations::FORMAT_WEBP);
    }
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main-dubai')->useDisk('abouts');
        $this->addMediaCollection('main-ua')->useDisk('abouts');
    }


}
