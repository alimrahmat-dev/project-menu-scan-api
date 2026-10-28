<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiningTableResource;
use App\Models\DiningTable;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    public function show(string $qrToken): JsonResponse
    {
        $table = DiningTable::query()
            ->where('qr_token', $qrToken)
            ->where('is_active', true)
            ->first();

        abort_if(! $table, 404, 'QR code meja tidak valid atau sudah tidak aktif.');

        return response()->json([
            'data' => new DiningTableResource($table),
            'restaurant' => config('menuscan.restaurant'),
        ]);
    }

    /**
     * Meja contoh untuk mode demo (tombol "Coba Demo").
     */
    public function demo(): JsonResponse
    {
        $table = DiningTable::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        abort_if(! $table, 404, 'Belum ada meja aktif. Jalankan seeder terlebih dahulu.');

        return response()->json([
            'data' => new DiningTableResource($table),
            'restaurant' => config('menuscan.restaurant'),
            'demo' => true,
        ]);
    }
}
