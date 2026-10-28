<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $menus = Menu::query()
            ->with('category')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('is_available'), fn ($query) => $query->where('is_available', $request->boolean('is_available')))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return MenuResource::collection($menus);
    }

    public function store(MenuRequest $request): JsonResponse
    {
        $menu = Menu::create($this->payload($request->validated()));

        return response()->json([
            'message' => 'Menu berhasil ditambahkan.',
            'data' => new MenuResource($menu->load('category')),
        ], 201);
    }

    public function update(MenuRequest $request, Menu $menu): JsonResponse
    {
        $data = $this->payload($request->validated());

        if ($data['name'] !== $menu->name) {
            $data['slug'] = Menu::uniqueSlug($data['name']);
        }

        $menu->update($data);

        return response()->json([
            'message' => 'Menu berhasil diperbarui.',
            'data' => new MenuResource($menu->load('category')),
        ]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();

        return response()->json(['message' => 'Menu berhasil dihapus.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_url' => $data['image_url'] ?? null,
            'is_available' => $data['is_available'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'calories' => $data['calories'] ?? null,
            'prep_time' => $data['prep_time'] ?? 15,
            'badge' => $data['badge'] ?? null,
        ];
    }
}
