<?php

namespace App\Data\Image\Response;

use Spatie\LaravelData\Data;

class UploadUrlResponseData extends Data
{
    public function __construct(
        public string $upload_url,
        public string $file_key,
    ) {}
}
