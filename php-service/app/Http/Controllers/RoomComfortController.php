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
            'temperature_c'      => $request->input('temperature_c', 26.0),
            'humidity_pct'       => $request->input('humidity_pct', 55.0),
            'traffic_density'    => $request->input('traffic_density', 8.0),
            'near_construction'  => $request->input('near_construction', 0),
            'population_density' => $request->input('population_density', 300),
            'vehicle_count'      => $request->input('vehicle_count', 15),
            'public_event'       => $request->input('public_event', 0),
            'school_zone'        => $request->input('school_zone', 1)
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