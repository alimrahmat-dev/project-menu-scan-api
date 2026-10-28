<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount('menus')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = Category::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => new CategoryResource($category->loadCount('menus')),
        ], 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($data['name'] !== $category->name) {
            $data['slug'] = Category::uniqueSlug($data['name']);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => new CategoryResource($category->loadCount('menus')),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->menus()->exists()) {
            return response()->json([
                'message' => 'Kategori masih memiliki menu. Pindahkan atau hapus menunya terlebih dahulu.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.'], Response::HTTP_OK);
    }
}
