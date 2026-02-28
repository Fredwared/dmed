<?php

namespace App\Data\Image\Request;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class UploadImageData extends Data
{
    public function __construct(
        #[Required, Mimes('jpeg', 'jpg', 'png'), Max(5120)]
        public UploadedFile $image,
    ) {}
}
