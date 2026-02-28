<?php

namespace App\Actions\Image;

use App\Data\Image\Response\ImageData;
use App\Models\Image;

class GetImageAction
{
    public function execute(Image $image): ImageData
    {
        return ImageData::fromImage($image);
    }
}
