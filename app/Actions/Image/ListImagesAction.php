<?php

namespace App\Actions\Image;

use App\Data\Image\Response\ImageListData;
use App\Models\User;

class ListImagesAction
{
    public function execute(User $user): ImageListData
    {
        $paginator = $user->images()
            ->latest()
            ->paginate(20);

        return ImageListData::fromPaginator($paginator);
    }
}
