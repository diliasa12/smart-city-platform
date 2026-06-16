<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Helpers\ApiResponse;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    private RabbitMQPublisher $publisher;

    public function __construct()
    {
        $this->publisher = new RabbitMQPublisher();
    }

    // POST /api/reports — submit laporan baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category'       => 'required|in:infrastructure,environment,traffic,public_safety,other',
            'description'    => 'required|string',
            'zone_id'        => 'required|integer',
            'attachment_url' => 'nullable|url|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ApiResponse::error('Validasi gagal', 422, $validator->errors()),
                422
            );
        }

        $report = Report::create([
            'citizen_id'     => $request->auth_user_id,
            'category'       => $request->category,
            'description'    => $request->description,
            'zone_id'        => $request->zone_id,
            'status'         => 'pending',
            'attachment_url' => $request->attachment_url,
        ]);

        $this->publisher->publish('report.submitted', [
            'report_id'  => $report->id,
            'citizen_id' => $report->citizen_id,
            'category'   => $report->category,
            'zone_id'    => $report->zone_id,
            'status'     => $report->status,
            'timestamp'  => now()->toISOString(),
        ]);

        return response()->json(
            ApiResponse::success($report, 'Laporan berhasil dikirim', 201),
            201
        );
    }

    // GET /api/reports — list laporan
    public function index(Request $request)
    {
        $authUserId   = $request->auth_user_id;
        $authUserRole = $request->auth_user_role;

        $query = Report::query();

        if ($authUserRole === 'citizen') {
            $query->where('citizen_id', $authUserId);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json(
            ApiResponse::success($reports, 'Data laporan berhasil diambil')
        );
    }

    // PATCH /api/reports/{id}/status — update status laporan
    public function updateStatus(Request $request, int $id)
    {
        if ($request->auth_user_role === 'citizen') {
            return response()->json(
                ApiResponse::error('Akses ditolak. Hanya admin/officer yang bisa update status.', 403),
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,resolved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ApiResponse::error('Validasi gagal', 422, $validator->errors()),
                422
            );
        }

        $report = Report::find($id);

        if (!$report) {
            return response()->json(
                ApiResponse::error('Laporan tidak ditemukan', 404),
                404
            );
        }

        $report->status = $request->status;
        if ($request->status === 'resolved') {
            $report->resolved_at = now();
        }
        $report->save();

        return response()->json(
            ApiResponse::success($report, 'Status laporan berhasil diperbarui')
        );
    }
}
