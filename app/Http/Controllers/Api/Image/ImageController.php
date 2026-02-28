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

class ImageController extends Controller
{
    public function store(UploadImageData $data, Request $request, UploadImageAction $action): JsonResponse
    {
        return response()->json(
            $action->execute($data->image, $request->user()),
            201,
        );
    }

    public function index(Request $request, ListImagesAction $action): JsonResponse
    {
        return response()->json($action->execute($request->user()));
    }

    public function show(Image $image, Request $request, GetImageAction $action): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($action->execute($image));
    }

    public function destroy(Image $image, Request $request, DeleteImageAction $action): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $action->execute($image);

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
