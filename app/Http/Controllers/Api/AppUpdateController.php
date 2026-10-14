<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppUpdateController extends Controller
{
    public const LATEST_VERSION_CODE = 2;
    public const LATEST_VERSION_NAME = '1.1.0';
    public const MIN_SUPPORTED_VERSION_CODE = 1;

    /**
     * Memeriksa ketersediaan pembaruan APK in-app (OTA non-Play Store).
     */
    public function checkUpdate(Request $request): JsonResponse
    {
        $clientVersionCode = (int) $request->query('version_code', 1);
        $hasUpdate = self::LATEST_VERSION_CODE > $clientVersionCode;
        $forceUpdate = $clientVersionCode < self::MIN_SUPPORTED_VERSION_CODE;

        $downloadUrl = url('/download/driver-finance-latest.apk');

        return response()->json([
            'status' => 'success',
            'has_update' => $hasUpdate,
            'force_update' => $forceUpdate,
            'latest_version_code' => self::LATEST_VERSION_CODE,
            'latest_version_name' => self::LATEST_VERSION_NAME,
            'client_version_code' => $clientVersionCode,
            'min_supported_version' => self::MIN_SUPPORTED_VERSION_CODE,
            'download_url' => $downloadUrl,
            'release_date' => '2026-10-15',
            'title' => 'Pembaruan Fitur Driver Finance v' . self::LATEST_VERSION_NAME,
            'changelog' => [
                'Intercom Voice Memo Helm dengan GPS Geolocation langsung ke Kotak Hitam',
                'Kalkulator COD Resto 3-digit & perhitungan kembalian fleksibel',
                'Misi Bebas Hutang 31 Oktober & kuota cicilan jatuh tempo harian',
                'Target Belanja Survival 5-Level (Beras, Mandi, Oli MPX2, HP Holder, Tabungan)',
                'Snapshot Saldo Likuid Driver 4 Elemen (Cash, SPay, GoPay Merchant, GoPay)',
                'Tampilan Mobile Responsive OLED-friendly & Bottom Navigation Bar',
            ],
        ]);
    }

    /**
     * Endpoint download langsung APK rilis terbaru.
     */
    public function downloadApk()
    {
        $apkPath = storage_path('app/public/releases/driver-finance-latest.apk');

        if (file_exists($apkPath)) {
            return response()->download($apkPath, 'driver-finance-v' . self::LATEST_VERSION_NAME . '.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'File APK belum diunggah ke server rilis.',
            'latest_version_name' => self::LATEST_VERSION_NAME,
            'build_instruction' => 'Build APK lokal via `flutter build apk --release` dan letakkan di storage/app/public/releases/driver-finance-latest.apk',
        ], 404);
    }
}
