# TASK-073: Layar Closing Mobile: Input Denominasi Fisik & Validasi Selisih Rp0

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Layar Audit Closing Mobile, Input Pecahan Uang Fisik, & Strict Lock Finalisasi  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun layar closing harian versi mobile (`ClosingScreen`) untuk driver yang ingin menyelesaikan rekonsiliasi malam langsung dari smartphone:
1. **Form Input Denominasi Fisik:**
   - Tabel/List input lembar dan keping uang:
     - Kertas: 100k, 50k, 20k, 10k, 5k, 2k, 1k.
     - Koin: 1.000, 500, 200, 100.
   - Perhitungan total fisik instan secara reaktif saat angka lembar diisi.
2. **Kalkulasi & Audit Selisih Realtime:**
   - Memanggil `POST /api/v1/reconciliation/calculate-difference` (sesuai `TASK-050`).
   - Menampilkan selisih:
     - Selisih = 0: Status Hijau *"SEIMBANG (KLOP)"* $\rightarrow$ Tombol **"Finalisasi Tutup Hari"** aktif.
     - Selisih $\ne$ 0: Status Merah/Kuning *"SELISIH Rp X"* $\rightarrow$ Tombol Finalisasi terkunci/disabled, driver diarahkan mendengarkan rekaman memo suara helm atau menunda closing ke web tanpa memblokir buka shift esok hari.
3. **Eksekusi Finalisasi:**
   - Memanggil `POST /api/v1/reconciliation/finalize` (sesuai `TASK-052`).
   - Menampilkan notifikasi sukses dan mengunci data hari tersebut.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/closing/presentation/closing_screen.dart` — Layar closing audit denominasi mobile.
2. `mobile/lib/features/closing/presentation/widgets/denomination_input_tile.dart` — Widget baris input pecahan uang.
3. `mobile/test/features/closing/presentation/closing_screen_test.dart` — Widget test audit closing mobile.

---

## 3. Spesifikasi Anatomi Kode (`closing_screen.dart`)
```dart
import 'package:flutter/material.dart';

class ClosingScreen extends StatefulWidget {
  final int bookBalance;
  final Future<void> Function(Map<int, int> denominations) onFinalize;

  const ClosingScreen({
    Key? key,
    required this.bookBalance,
    required this.onFinalize,
  }) : super(key: key);

  @override
  State<ClosingScreen> createState() => _ClosingScreenState();
}

class _ClosingScreenState extends State<ClosingScreen> {
  final Map<int, int> _counts = {
    100000: 0, 50000: 0, 20000: 0, 10000: 0, 5000: 0, 2000: 0, 1000: 0, 500: 0
  };

  int get _actualPhysicalTotal {
    return _counts.entries.fold(0, (sum, entry) => sum + (entry.key * entry.value));
  }

  int get _difference => _actualPhysicalTotal - widget.bookBalance;

  @override
  Widget build(BuildContext context) {
    final isBalanced = _difference == 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Closing Shift Harian')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Saldo Kas Buku:', style: TextStyle(color: Color(0xFF94A3B8))),
                Text('Rp ${widget.bookBalance}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          // Ringkasan Selisih
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: isBalanced ? const Color(0xFF064E3B) : const Color(0xFF7F1D1D),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Center(
              child: Text(
                isBalanced ? 'STATUS: KLOP (Rp 0)' : 'SELISIH: Rp $_difference',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15),
              ),
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: isBalanced ? const Color(0xFF10B981) : Colors.grey.shade700,
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            onPressed: isBalanced ? () => widget.onFinalize(_counts) : null,
            child: const Text('Finalisasi Tutup Hari', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Layar `ClosingScreen` mengkalkulasi total fisik aktual secara reaktif dan membandingkannya dengan saldo buku.
2. Tombol Finalisasi hanya aktif (`enabled`) jika selisih tepat Rp0, dan terkunci (`disabled`) jika terdapat selisih.
3. Widget test memverifikasi tombol terkunci saat selisih $\ne 0$ dan aktif saat selisih $= 0$.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/closing/presentation/closing_screen_test.dart`
- **Metode Uji:**
  1. `testWidgets('Finalize button is disabled when physical total != book balance', (tester) async)`:
     - Render `ClosingScreen(bookBalance: 100000)`.
     - Verifikasi tombol Finalisasi dalam keadaan disabled.
  2. `testWidgets('Finalize button becomes enabled when difference is exactly 0', (tester) async)`:
     - Set denominasi 1 lembar 100k $\rightarrow$ Selisih = Rp0 $\rightarrow$ Verifikasi tombol aktif dan callback dipanggil saat di-tap.
