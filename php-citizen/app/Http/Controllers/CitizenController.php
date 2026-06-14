<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CitizenController extends Controller
{
    // POST /api/citizens — daftarkan warga baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik'      => 'required|string|size:16|unique:citizen_citizens,nik',
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:citizen_citizens,email',
            'phone'    => 'nullable|string|max:20',
            'zone_id'  => 'required|integer',
            'password' => 'required|string|min:6',
            'role'     => 'nullable|in:citizen,admin,officer',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ApiResponse::error('Validasi gagal', 422, $validator->errors()),
                422
            );
        }

        $citizen = Citizen::create([
            'nik'      => $request->nik,
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'zone_id'  => $request->zone_id,
            'role'     => $request->role ?? 'citizen',
            'password' => Hash::make($request->password),
        ]);

        return response()->json(
            ApiResponse::success($citizen, 'Warga berhasil didaftarkan', 201),
            201
        );
    }

    // GET /api/citizens/{id} — lihat profil warga
    public function show(Request $request, int $id)
    {
        $authUserId   = $request->auth_user_id;
        $authUserRole = $request->auth_user_role;

        if ($authUserRole === 'citizen' && $authUserId !== $id) {
            return response()->json(
                ApiResponse::error('Akses ditolak', 403),
                403
            );
        }

        $citizen = Citizen::find($id);

        if (!$citizen) {
            return response()->json(
                ApiResponse::error('Warga tidak ditemukan', 404),
                404
            );
        }

        return response()->json(
            ApiResponse::success($citizen, 'Data warga berhasil diambil')
        );
    }
}