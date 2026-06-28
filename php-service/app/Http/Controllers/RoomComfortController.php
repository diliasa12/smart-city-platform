<?php

namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 

class RoomComfortController extends Controller
{
    public function analyze(Request $request)
    {
        // Tangkap data yang dikirim dari IoT / Wokwi / Postman
        // set nilai default jika tidak ada input
        $payload = [
            'room_id'       => $request->input('room_id', 1),
            'temperature'   => $request->input('temperature', 26.0),
            'humidity'      => $request->input('humidity', 55.0),
            'decibel_level' => $request->input('decibel_level', 40.0)
        ];

        try {
            // Tembak ke Container Python 
            $response = Http::post('http://python-ml:5000/api/v1/analyze-comfort', $payload);

            // Cek Python membalas dengan sukses
            if ($response->successful()) {
                $mlResult = $response->json();
                

                // return jawaban Python ke Frontend / IoT
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil dianalisis oleh ML',
                    'data_sensor' => $payload,
                    'hasil_ai' => $mlResult
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'ML Service return error'], 500);

        } catch (\Exception $e) {
            // Jika Python-nya mati atau nama container salah
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal koneksi ke ML Service: ' . $e->getMessage()
            ], 500);
        }
    }
}