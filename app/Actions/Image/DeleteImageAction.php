<?php

namespace App\Actions\Image;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class DeleteImageAction
{
    public function execute(Image $image): void
    {
        Storage::disk('s3')->delete($image->storage_path);

        $image->delete();
    }
}
