<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::orderBy('name', 'asc')->get();
        return response()->json(ApiResponse::success($zones, 'Data wilayah berhasil diambil'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:shared_zones,name',
            'city_district' => 'required|string|max:100',
            'coordinates' => 'required|json',
            'area_km2' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error('Validasi gagal', 422, $validator->errors()), 422);
        }

        $zone = Zone::create([
            'name' => $request->name,
            'city_district' => $request->city_district,
            'coordinates' => json_decode($request->coordinates, true),
            'area_km2' => $request->area_km2,
        ]);

        return response()->json(ApiResponse::success($zone, 'Wilayah berhasil ditambahkan', 201), 201);
    }

    public function show($id)
    {
        $zone = Zone::find($id);
        if (!$zone) {
            return response()->json(ApiResponse::error('Wilayah tidak ditemukan', 404), 404);
        }
        return response()->json(ApiResponse::success($zone, 'Detail wilayah berhasil diambil'));
    }

    public function update(Request $request, $id)
    {
        $zone = Zone::find($id);
        if (!$zone) {
            return response()->json(ApiResponse::error('Wilayah tidak ditemukan', 404), 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:100|unique:shared_zones,name,' . $id,
            'city_district' => 'string|max:100',
            'coordinates' => 'json',
            'area_km2' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(ApiResponse::error('Validasi gagal', 422, $validator->errors()), 422);
        }

        if ($request->has('coordinates')) {
            $request->merge(['coordinates' => json_decode($request->coordinates, true)]);
        }

        $zone->update($request->all());

        return response()->json(ApiResponse::success($zone, 'Wilayah berhasil diperbarui'));
    }

    public function destroy($id)
    {
        $zone = Zone::find($id);
        if (!$zone) {
            return response()->json(ApiResponse::error('Wilayah tidak ditemukan', 404), 404);
        }

       
        if ($zone->rooms()->count() > 0) {
            return response()->json(ApiResponse::error('Gagal hapus: Wilayah ini masih memiliki ruangan terdaftar', 400), 400);
        }

        $zone->delete();
        return response()->json(ApiResponse::success(null, 'Wilayah berhasil dihapus'));
    }
}