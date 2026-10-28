<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MenuResource;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['menus' => fn ($query) => $query->available()->orderBy('name')])
            ->get();

        $featured = Menu::query()
            ->available()
            ->where('is_featured', true)
            ->with('category')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json([
            'restaurant' => config('menuscan.restaurant'),
            'currency' => 'IDR',
            'tax_percent' => config('menuscan.tax_percent'),
            'service_percent' => config('menuscan.service_percent'),
            'categories' => CategoryResource::collection($categories),
            'featured' => MenuResource::collection($featured),
        ]);
    }

    public function show(Request $request, Menu $menu): MenuResource
    {
        abort_unless($menu->is_available, 404, 'Menu tidak tersedia.');

        return new MenuResource($menu->load('category'));
    }
}
