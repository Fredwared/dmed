<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

class CreateBucketCommand extends Command
{
    protected $signature = 'app:create-bucket';

    protected $description = 'Create the S3 bucket if it does not exist';

    public function handle(): int
    {
        $bucket = (string) config('filesystems.disks.s3.bucket');

        /** @var AwsS3V3Adapter $adapter */
        $adapter = Storage::disk('s3');

        /** @var S3Client $client */
        $client = $adapter->getClient();

        try {
            $client->headBucket(['Bucket' => $bucket]);
            $this->info("Bucket '{$bucket}' already exists.");
        } catch (\Throwable) {
            try {
                $client->createBucket(['Bucket' => $bucket]);
                $this->info("Bucket '{$bucket}' created.");
            } catch (\Throwable $e) {
                $this->error("Failed to create bucket: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
