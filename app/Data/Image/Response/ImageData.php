<?php

namespace App\Data\Image\Response;

use App\Models\Image;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;

class ImageData extends Data
{
    public function __construct(
        public int $id,
        public string $original_filename,
        public string $mime_type,
        public int $file_size,
        public ?int $width,
        public ?int $height,
        public ?string $url,
        public string $status,
        public CarbonImmutable $created_at,
    ) {}

    public static function fromImage(Image $image): self
    {
        $url = null;

        if ($image->status === Image::STATUS_READY) {
            $disk = Storage::disk('s3');

            try {
                $url = $disk->temporaryUrl($image->storage_path, now()->addHour());
            } catch (\RuntimeException) {
                $url = $disk->url($image->storage_path);
            }

            $endpoint = (string) config('filesystems.disks.s3.endpoint');
            $publicUrl = (string) config('filesystems.disks.s3.url');

            if ($url && $endpoint && $publicUrl && $endpoint !== $publicUrl) {
                $url = str_replace($endpoint, $publicUrl, $url);
            }
        }

        return new self(
            id: $image->id,
            original_filename: $image->original_filename,
            mime_type: $image->mime_type,
            file_size: $image->file_size,
            width: $image->width,
            height: $image->height,
            url: $url,
            status: $image->status,
            created_at: CarbonImmutable::parse($image->created_at),
        );
    }
}
