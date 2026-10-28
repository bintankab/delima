<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaLibraryUploadRequest;
use App\Models\MediaLibraryItemModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MediaLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = MediaLibraryItemModel::query()
            ->with('media')
            ->latest()
            ->paginate($this->perPage($request, 24))
            ->withQueryString()
            ->through(fn (MediaLibraryItemModel $item): array => $this->present($item));

        return response()->json($items);
    }

    public function store(MediaLibraryUploadRequest $request): JsonResponse
    {
        $item = MediaLibraryItemModel::query()->create(['user_id' => Auth::id()]);
        $item->addMediaFromRequest('file')->toMediaCollection('file');

        return response()->json($this->present($item->fresh('media')), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(MediaLibraryItemModel $item): array
    {
        $media = $item->getFirstMedia('file');

        return [
            'id' => $item->id,
            'url' => $this->relativePath($media?->getUrl()),
            'thumb' => $this->relativePath($media?->getUrl('thumb')),
            'name' => $media?->file_name,
            'size' => $media?->human_readable_size,
            'created_at' => $item->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Strip the scheme and host from a media URL, so the path stored in rich
     * text content stays valid if APP_URL (domain) changes later.
     */
    private function relativePath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return parse_url($url, PHP_URL_PATH) ?? $url;
    }
}
