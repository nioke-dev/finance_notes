# TASK-089: Halaman Web Audit Log Explorer (`GET /audit-logs`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller, View Blade, & Filter Riwayat Audit Trail Mutasi/Koreksi  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun halaman penjelajah audit log di `/audit-logs` untuk melihat transparansi seluruh jejak koreksi, pembatalan, dan penyesuaian saldo:
1. **Fitur Audit Log Explorer:**
   - Tabel kronologis event audit (`created_at`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `reason`).
   - Badge aksi berwarna: Hijau (`create`), Kuning/Amber (`update/correction`), Merah (`reversal`).
   - Modal detail JSON perbandingan sebelum dan sesudah koreksi (*diff view*).
2. **Filter & Pencarian:**
   - Filter berdasarkan entitas (`Account`, `Transaction`, `WorkDay`, `Debt`).
   - Filter rentang tanggal.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/AuditLogWebController.php` — Controller eksplorasi audit log.
2. `resources/views/pages/audit_logs/index.blade.php` — Halaman Blade tabel riwayat audit trail.
3. `routes/web.php` — Pendaftaran route `GET /audit-logs`.
4. `tests/Feature/Web/AuditLogWebTest.php` — Automated test halaman audit log explorer.

---

## 3. Spesifikasi Anatomi Kode (`AuditLogWebController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogWebController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('entity_type')) {
            $query->where('auditable_type', 'LIKE', '%' . $request->input('entity_type') . '%');
        }

        $logs = $query->paginate(20);

        return view('pages.audit_logs.index', compact('logs'));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Rute `GET /audit-logs` merender view tabel audit log dengan status HTTP 200.
2. Menampilkan kolom nilai lama, nilai baru, dan alasan koreksi secara jelas.
3. Mendukung paginasi data 20 item per halaman.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/AuditLogWebTest.php`
- **Metode Uji:**
  1. `test_audit_log_page_renders_chronological_logs()`:
     - Buat data `AuditLog` $\rightarrow$ Akses `GET /audit-logs` $\rightarrow$ Pastikan HTTP 200 dan data log tertera di HTML.
