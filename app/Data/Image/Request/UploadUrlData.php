<?php

namespace App\Data\Image\Request;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UploadUrlData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $filename,

        #[Required, StringType, In('image/jpeg', 'image/png')]
        public string $mime_type,
    ) {}
}
