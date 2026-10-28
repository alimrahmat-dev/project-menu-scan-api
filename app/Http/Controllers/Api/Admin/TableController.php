<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TableRequest;
use App\Http\Resources\DiningTableResource;
use App\Models\DiningTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TableController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tables = DiningTable::query()
            ->withCount('orders')
            ->orderBy('code')
            ->get();

        return DiningTableResource::collection($tables);
    }

    public function store(TableRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);

        $table = DiningTable::create($data);

        return response()->json([
            'message' => 'Meja berhasil ditambahkan.',
            'data' => new DiningTableResource($table),
        ], 201);
    }

    public function update(TableRequest $request, DiningTable $table): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);

        $table->update($data);

        return response()->json([
            'message' => 'Meja berhasil diperbarui.',
            'data' => new DiningTableResource($table->loadCount('orders')),
        ]);
    }

    public function destroy(DiningTable $table): JsonResponse
    {
        $table->delete();

        return response()->json(['message' => 'Meja berhasil dihapus.']);
    }

    public function regenerate(DiningTable $table): JsonResponse
    {
        $table->forceFill(['qr_token' => (string) Str::uuid()])->save();

        return response()->json([
            'message' => 'QR code berhasil dibuat ulang. QR lama tidak berlaku lagi.',
            'data' => new DiningTableResource($table->loadCount('orders')),
        ]);
    }
}
