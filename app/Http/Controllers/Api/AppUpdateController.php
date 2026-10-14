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

        // Fallback langsung ke GitHub Release binary APK
        return redirect()->away('https://github.com/nioke-dev/finance_notes/releases/download/v1.1.0-release/driver-finance-latest.apk');
    }

    /**
     * Menerima log build dari CI untuk pemantauan otomatis tanpa otentikasi rumit.
     */
    public function receiveCiLog(Request $request): JsonResponse
    {
        $logContent = $request->getContent();
        Storage::disk('public')->put('ci_build.log', $logContent);
        return response()->json(['status' => 'received', 'bytes' => strlen($logContent)]);
    }

    /**
     * Melihat log build CI secara langsung.
     */
    public function viewCiLog()
    {
        if (Storage::disk('public')->exists('ci_build.log')) {
            $log = Storage::disk('public')->get('ci_build.log');
            return response($log, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        return response('Belum ada log build CI yang diterima.', 404, ['Content-Type' => 'text/plain']);
    }

    /**
     * Menerima file APK rilis langsung dari runner GitHub Actions.
     */
    public function uploadReleaseApk(Request $request): JsonResponse
    {
        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            $file->storeAs('releases', 'driver-finance-latest.apk', 'public');
            return response()->json(['status' => 'success', 'message' => 'APK uploaded successfully']);
        }

        $rawContent = $request->getContent();
        if (!empty($rawContent)) {
            Storage::disk('public')->put('releases/driver-finance-latest.apk', $rawContent);
            return response()->json(['status' => 'success', 'message' => 'APK stream saved successfully']);
        }

        return response()->json(['status' => 'error', 'message' => 'No APK payload received'], 400);
    }
}
