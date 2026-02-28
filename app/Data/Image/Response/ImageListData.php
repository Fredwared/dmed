<?php

namespace App\Data\Image\Response;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

class ImageListData extends Data
{
    /**
     * @param  ImageData[]  $data
     */
    public function __construct(
        public array $data,
        public int $current_page,
        public int $last_page,
        public int $per_page,
        public int $total,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            data: $paginator->getCollection()
                ->map(fn ($image) => ImageData::fromImage($image))
                ->all(),
            current_page: $paginator->currentPage(),
            last_page: $paginator->lastPage(),
            per_page: $paginator->perPage(),
            total: $paginator->total(),
        );
    }
}
