<?php

namespace App\Http\Controllers;

use App\Models\EnvRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class EnvRoomController extends Controller
{
    /**
     * READ (Semua Data)
     * Mengambil daftar semua ruangan beserta data zona terkait.
     */
    public function index(): JsonResponse
    {
        $rooms = EnvRoom::with('zone')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar ruangan berhasil diambil.',
            'data' => $rooms
        ], 200);
    }

    /**
     * CREATE
     * Menyimpan data ruangan baru ke database dengan validasi ketat.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:shared_zones,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:0',
            'device_token' => 'required|string|max:128|unique:env_rooms,device_token',
            'is_active' => 'sometimes|boolean'
        ]);

        $room = EnvRoom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan.',
            'data' => $room
        ], 201);
    }

    /**
     * READ (Spesifik Data)
     * Mengambil detail satu ruangan berdasarkan ID.
     */
    public function show(int $id): JsonResponse
    {
        $room = EnvRoom::with('zone')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail ruangan ditemukan.',
            'data' => $room
        ], 200);
    }

    /**
     * UPDATE
     * Memperbarui data ruangan berdasarkan ID dengan pengecualian unique rule untuk token sendiri.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $room = EnvRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        $validated = $request->validate([
            'zone_id' => 'sometimes|required|integer|exists:shared_zones,id',
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'sometimes|required|integer|min:0',
            'device_token' => [
                'sometimes',
                'required',
                'string',
                'max:128',
                Rule::unique('env_rooms', 'device_token')->ignore($room->id)
            ],
            'is_active' => 'sometimes|boolean'
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data ruangan berhasil diperbarui.',
            'data' => $room
        ], 200);
    }

    /**
     * DELETE
     * Menghapus data ruangan dari database.
     */
    public function destroy(int $id): JsonResponse
    {
        $room = EnvRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus.'
        ], 200);
    }
}