<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index()
    {
        
        $rooms = Room::with('zone')->orderBy('created_at', 'desc')->get();
        return response()->json(ApiResponse::success($rooms, 'Data ruangan berhasil diambil'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:shared_zones,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error('Validasi gagal', 422, $validator->errors()), 422);
        }

        
        $deviceToken = 'iot_' . Str::random(40);

        $room = Room::create([
            'zone_id' => $request->zone_id,
            'room_name' => $request->room_name,
            'capacity' => $request->capacity,
            'device_token' => $deviceToken,
            'is_active' => 1,
        ]);

        return response()->json(ApiResponse::success($room, 'Ruangan berhasil didaftarkan beserta Device Token', 201), 201);
    }

    public function show($id)
    {
        $room = Room::with('zone')->find($id);
        if (!$room) {
            return response()->json(ApiResponse::error('Ruangan tidak ditemukan', 404), 404);
        }
        return response()->json(ApiResponse::success($room, 'Detail ruangan berhasil diambil'));
    }

    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(ApiResponse::error('Ruangan tidak ditemukan', 404), 404);
        }

        $validator = Validator::make($request->all(), [
            'zone_id' => 'exists:shared_zones,id',
            'room_name' => 'string|max:100',
            'capacity' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error('Validasi gagal', 422, $validator->errors()), 422);
        }

        $room->update($request->all());

        return response()->json(ApiResponse::success($room, 'Data ruangan berhasil diperbarui'));
    }

    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(ApiResponse::error('Ruangan tidak ditemukan', 404), 404);
        }

        $room->delete();
        return response()->json(ApiResponse::success(null, 'Data ruangan berhasil dihapus'));
    }
}