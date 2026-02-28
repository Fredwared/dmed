<?php

namespace App\Data\Image\Request;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ConfirmUploadData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $file_key,
    ) {}
}
