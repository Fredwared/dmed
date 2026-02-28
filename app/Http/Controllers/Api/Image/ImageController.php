<?php

namespace App\Http\Controllers\Api\Image;

use App\Actions\Image\ConfirmUploadAction;
use App\Actions\Image\DeleteImageAction;
use App\Actions\Image\GenerateUploadUrlAction;
use App\Actions\Image\GetImageAction;
use App\Actions\Image\ListImagesAction;
use App\Data\Image\Request\ConfirmUploadData;
use App\Data\Image\Request\UploadUrlData;
use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Images
 *
 * Endpoints for uploading, listing, viewing, and deleting images.
 * All endpoints require authentication. Users can only manage their own images.
 */
class ImageController extends Controller
{
    /**
     * Get Upload URL
     *
     * Get a pre-signed S3 URL for direct file upload. The client should PUT the file
     * directly to the returned URL, then call the confirm endpoint with the file_key.
     *
     * @authenticated
     *
     * @bodyParam filename string required Original filename. Example: photo.jpg
     * @bodyParam mime_type string required MIME type. Must be image/jpeg or image/png. Example: image/jpeg
     *
     * @response 200 {"upload_url":"https://s3.example.com/uploads/1/uuid.jpg?X-Amz-Signature=...","file_key":"uploads/1/uuid.jpg"}
     * @response 422 scenario="Validation error" {"message":"The mime type field must be one of: image/jpeg, image/png.","errors":{"mime_type":["The mime type field must be one of: image/jpeg, image/png."]}}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function uploadUrl(UploadUrlData $data, Request $request, GenerateUploadUrlAction $action): JsonResponse
    {
        return response()->json(
            $action->execute($data->filename, $data->mime_type, $request->user()),
        );
    }

    /**
     * Confirm Upload
     *
     * Confirm that a file has been uploaded to S3. This validates the file,
     * creates the database record, and dispatches background processing (WebP compression).
     * Duplicate files (same content) return the existing image.
     *
     * @authenticated
     *
     * @bodyParam file_key string required S3 file key returned from upload-url endpoint. Example: uploads/1/550e8400-e29b-41d4-a716-446655440000.jpg
     *
     * @response 201 {"id":1,"original_filename":"uuid.jpg","mime_type":"image/jpeg","file_size":204800,"width":null,"height":null,"url":null,"status":"pending","created_at":"2026-02-28T10:00:00.000000Z"}
     * @response 422 scenario="File not found" {"message":"The given data was invalid.","errors":{"file_key":["File not found on storage."]}}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function confirm(ConfirmUploadData $data, Request $request, ConfirmUploadAction $action): JsonResponse
    {
        return response()->json(
            $action->execute($data->file_key, $request->user()),
            201,
        );
    }

    /**
     * List Images
     *
     * Get a paginated list of the authenticated user's images (20 per page, newest first).
     *
     * @authenticated
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data":[{"id":1,"original_filename":"photo.jpg","mime_type":"image/webp","file_size":204800,"width":1920,"height":1080,"url":"https://s3.example.com/images/1/abc123.webp?signature=...","status":"ready","created_at":"2026-02-28T10:00:00.000000Z"}],"current_page":1,"last_page":1,"per_page":20,"total":1}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function index(Request $request, ListImagesAction $action): JsonResponse
    {
        return response()->json($action->execute($request->user()));
    }

    /**
     * Show Image
     *
     * Get details and a signed URL for a specific image owned by the authenticated user.
     *
     * @authenticated
     *
     * @urlParam image integer required The image ID. Example: 1
     *
     * @response 200 {"id":1,"original_filename":"photo.jpg","mime_type":"image/webp","file_size":204800,"width":1920,"height":1080,"url":"https://s3.example.com/images/1/abc123.webp?signature=...","status":"ready","created_at":"2026-02-28T10:00:00.000000Z"}
     * @response 403 scenario="Forbidden" {"message":"Forbidden"}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Image] 99999"}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function show(Image $image, Request $request, GetImageAction $action): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($action->execute($image));
    }

    /**
     * Delete Image
     *
     * Delete an image owned by the authenticated user. Removes both the database record and the S3 file.
     *
     * @authenticated
     *
     * @urlParam image integer required The image ID. Example: 1
     *
     * @response 200 {"message":"Image deleted successfully"}
     * @response 403 scenario="Forbidden" {"message":"Forbidden"}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Image] 99999"}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function destroy(Image $image, Request $request, DeleteImageAction $action): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $action->execute($image);

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
