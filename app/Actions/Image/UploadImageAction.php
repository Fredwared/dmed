<?php

namespace App\Actions\Image;

use App\Data\Image\Response\ImageData;
use App\Models\Image;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UploadImageAction
{
    public function execute(UploadedFile $file, User $user): ImageData
    {
        $fileHash = hash_file('sha256', $file->getRealPath());
        $storagePath = "images/{$user->id}/{$fileHash}.webp";

        $existing = Image::where('user_id', $user->id)
            ->where('file_hash', $fileHash)
            ->first();

        if ($existing) {
            return ImageData::fromImage($existing);
        }

        $manager = new ImageManager(new Driver());
        $interventionImage = $manager->read($file->getRealPath());
        $encoded = $interventionImage->toWebp(80);

        Storage::disk('s3')->put($storagePath, (string) $encoded);

        $width = $interventionImage->width();
        $height = $interventionImage->height();

        try {
            $image = Image::create([
                'user_id' => $user->id,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $storagePath,
                'mime_type' => 'image/webp',
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'width' => $width,
                'height' => $height,
            ]);
        } catch (UniqueConstraintViolationException) {
            $image = Image::where('user_id', $user->id)
                ->where('file_hash', $fileHash)
                ->firstOrFail();
        }

        return ImageData::fromImage($image);
    }
}
