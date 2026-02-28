<?php

namespace App\Actions\Image;

use App\Data\Image\Response\UploadUrlResponseData;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateUploadUrlAction
{
    public function execute(string $filename, string $mimeType, User $user): UploadUrlResponseData
    {
        $extension = match ($mimeType) {
            'image/png' => 'png',
            default => 'jpg',
        };

        $fileKey = "uploads/{$user->id}/".Str::uuid().".{$extension}";

        $presigned = Storage::disk('s3')->temporaryUploadUrl(
            $fileKey,
            now()->addMinutes(5),
            ['ContentType' => $mimeType],
        );

        return new UploadUrlResponseData(
            upload_url: $presigned['url'],
            file_key: $fileKey,
        );
    }
}
