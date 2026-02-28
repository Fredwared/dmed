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

        $url = $presigned['url'];

        $endpoint = (string) config('filesystems.disks.s3.endpoint');
        $publicUrl = (string) config('filesystems.disks.s3.url');

        if ($endpoint && $publicUrl && $endpoint !== $publicUrl) {
            $url = str_replace($endpoint, $publicUrl, $url);
        }

        return new UploadUrlResponseData(
            upload_url: $url,
            file_key: $fileKey,
        );
    }
}
