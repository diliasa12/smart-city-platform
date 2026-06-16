<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class NotifController extends Controller
{
    // GET /api/notifications — ambil notifikasi milik warga yang login
    public function index(Request $request)
    {
        $notifications = Notification::where('citizen_id', $request->auth_user_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(
            ApiResponse::success($notifications, 'Notifikasi berhasil diambil')
        );
    }
}