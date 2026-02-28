<?php

namespace App\Actions\Image;

use App\Data\Image\Response\ImageData;
use App\Jobs\ProcessImageJob;
use App\Models\Image;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConfirmUploadAction
{
    public function execute(string $fileKey, User $user): ImageData
    {
        $disk = Storage::disk('s3');

        if (! $disk->exists($fileKey)) {
            throw ValidationException::withMessages([
                'file_key' => ['File not found on storage.'],
            ]);
        }

        $fileSize = $disk->size($fileKey);

        if ($fileSize > 5 * 1024 * 1024) {
            $disk->delete($fileKey);

            throw ValidationException::withMessages([
                'file_key' => ['File size exceeds 5MB limit.'],
            ]);
        }

        $contents = $disk->get($fileKey);
        $fileHash = hash('sha256', $contents);
        $storagePath = "images/{$user->id}/{$fileHash}.webp";

        $existing = Image::where('user_id', $user->id)
            ->where('file_hash', $fileHash)
            ->first();

        if ($existing) {
            $disk->delete($fileKey);

            return ImageData::fromImage($existing);
        }

        $originalFilename = basename($fileKey);

        try {
            $image = Image::create([
                'user_id' => $user->id,
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'mime_type' => $disk->mimeType($fileKey),
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'status' => Image::STATUS_PENDING,
                'width' => null,
                'height' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
            $disk->delete($fileKey);

            $image = Image::where('user_id', $user->id)
                ->where('file_hash', $fileHash)
                ->firstOrFail();

            return ImageData::fromImage($image);
        }

        ProcessImageJob::dispatch($image->id, $fileKey);

        return ImageData::fromImage($image);
    }
}
