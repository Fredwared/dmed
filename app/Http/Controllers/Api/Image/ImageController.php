<?php

namespace App\Http\Controllers\Api\Image;

use App\Actions\Image\DeleteImageAction;
use App\Actions\Image\GetImageAction;
use App\Actions\Image\ListImagesAction;
use App\Actions\Image\UploadImageAction;
use App\Data\Image\Request\UploadImageData;
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
     * Upload Image
     *
     * Upload a PNG or JPEG image (max 5MB). The image is compressed to WebP format.
     * Duplicate uploads (same file content) return the existing image instead of creating a new one.
     *
     * @authenticated
     *
     * @bodyParam image file required The image file to upload. Must be JPEG or PNG, max 5MB. No-example
     *
     * @response 201 {"id":1,"original_filename":"photo.jpg","mime_type":"image/webp","file_size":204800,"width":1920,"height":1080,"url":"https://s3.example.com/images/1/abc123.webp?signature=...","created_at":"2026-02-28T10:00:00.000000Z"}
     * @response 422 scenario="Validation error" {"message":"The image field must be a file of type: jpeg, jpg, png.","errors":{"image":["The image field must be a file of type: jpeg, jpg, png."]}}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function store(UploadImageData $data, Request $request, UploadImageAction $action): JsonResponse
    {
        return response()->json(
            $action->execute($data->image, $request->user()),
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
     * @response 200 {"data":[{"id":1,"original_filename":"photo.jpg","mime_type":"image/webp","file_size":204800,"width":1920,"height":1080,"url":"https://s3.example.com/images/1/abc123.webp?signature=...","created_at":"2026-02-28T10:00:00.000000Z"}],"current_page":1,"last_page":1,"per_page":20,"total":1}
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
     * @response 200 {"id":1,"original_filename":"photo.jpg","mime_type":"image/webp","file_size":204800,"width":1920,"height":1080,"url":"https://s3.example.com/images/1/abc123.webp?signature=...","created_at":"2026-02-28T10:00:00.000000Z"}
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
