<?php

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $imageId,
        public readonly string $uploadKey,
    ) {}

    public function handle(): void
    {
        $image = Image::findOrFail($this->imageId);
        $disk = Storage::disk('s3');

        if ($image->status === Image::STATUS_READY) {
            $disk->delete($this->uploadKey);

            return;
        }

        try {
            $contents = $disk->get($this->uploadKey);

            $manager = new ImageManager(new Driver());
            $interventionImage = $manager->read($contents);
            $encoded = $interventionImage->toWebp(80);

            $disk->put($image->storage_path, (string) $encoded);

            $image->update([
                'width' => $interventionImage->width(),
                'height' => $interventionImage->height(),
                'mime_type' => 'image/webp',
                'status' => Image::STATUS_READY,
            ]);
        } catch (\Throwable $e) {
            $image->update(['status' => Image::STATUS_FAILED]);

            throw $e;
        } finally {
            $disk->delete($this->uploadKey);
        }
    }

    public function failed(\Throwable $e): void
    {
        Image::where('id', $this->imageId)
            ->where('status', '!=', Image::STATUS_READY)
            ->update(['status' => Image::STATUS_FAILED]);
    }
}
