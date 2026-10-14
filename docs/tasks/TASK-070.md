# TASK-070: BottomSheet Quick Bensin Flutter (Toggle SPBU vs Madura & Auto-liter)

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Modal BottomSheet Input Cepat Bensin, Selector SPBU/Madura & Quick Chips  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun widget Modal BottomSheet interaktif `QuickFuelBottomSheet` untuk mencatat pembelian bensin motor secara instan di pinggir jalan:
1. **Toggle Pilihan Jenis Pengisian:**
   - **SPBU Resmi:** Menghitung estimasi liter otomatis berdasarkan harga Rp10.000/Liter (Pertalite).
   - **Eceran / Madura:** Menghitung estimasi botol berdasarkan harga Rp12.000/Botol.
2. **Quick Preset Chips:**
   - Jika SPBU terpilih: Chips nominal Rp15.000 (1.5 L), Rp20.000 (2.0 L), Rp25.000 (2.5 L), Rp30.000 (3.0 L - Full Tank Vario).
   - Jika Madura terpilih: Chips 1 Botol (Rp12.000), 2 Botol (Rp24.000).
3. **Pilihan Sumber Dana Pembayaran:**
   - Default: Kas Tunai di Dompet (paling sering saat narik).
   - Opsi Secondary: BCA / GoPay.
4. **Trigger API:** Memanggil `POST /api/v1/quick-actions/fuel` (sesuai `TASK-034`) dan menutup modal dengan snackbar konfirmasi sukses.

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/lib/features/quick_actions/presentation/widgets/quick_fuel_bottom_sheet.dart` — Bottom sheet modal input bensin.
2. `mobile/test/features/quick_actions/presentation/quick_fuel_bottom_sheet_test.dart` — Widget test modal bensin.

---

## 3. Spesifikasi Anatomi Kode (`quick_fuel_bottom_sheet.dart`)
```dart
import 'package:flutter/material.dart';

enum FuelType { spbu, madura }

class QuickFuelBottomSheet extends StatefulWidget {
  final Future<void> Function({required String fuelType, required int amount, required String paymentSource}) onSubmit;

  const QuickFuelBottomSheet({Key? key, required this.onSubmit}) : super(key: key);

  @override
  State<QuickFuelBottomSheet> createState() => _QuickFuelBottomSheetState();
}

class _QuickFuelBottomSheetState extends State<QuickFuelBottomSheet> {
  FuelType _selectedType = FuelType.spbu;
  int _selectedAmount = 20000;
  String _paymentSource = 'cash';

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: const BoxDecoration(
        color: Color(0xFF1E293B),
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Catat Bensin Motor', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
              IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close, color: Colors.grey)),
            ],
          ),
          const SizedBox(height: 12),
          // Toggle Switch
          SegmentedButton<FuelType>(
            segments: const [
              ButtonSegment(value: FuelType.spbu, label: Text('SPBU Resmi (10k/L)')),
              ButtonSegment(value: FuelType.madura, label: Text('Madura (12k/Btl)')),
            ],
            selected: {_selectedType},
            onSelectionChanged: (set) => setState(() {
              _selectedType = set.first;
              _selectedAmount = _selectedType == FuelType.spbu ? 20000 : 12000;
            }),
          ),
          const SizedBox(height: 16),
          // Chips
          Wrap(
            spacing: 8,
            children: _selectedType == FuelType.spbu
                ? [15000, 20000, 25000, 30000].map((amt) => ChoiceChip(
                    label: Text('Rp ${amt / 1000}k'),
                    selected: _selectedAmount == amt,
                    onSelected: (val) => setState(() => _selectedAmount = amt),
                  )).toList()
                : [12000, 24000].map((amt) => ChoiceChip(
                    label: Text('${amt ~/ 12000} Botol (Rp ${amt / 1000}k)'),
                    selected: _selectedAmount == amt,
                    onSelected: (val) => setState(() => _selectedAmount = amt),
                  )).toList(),
          ),
          const SizedBox(height: 20),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            onPressed: () async {
              await widget.onSubmit(
                fuelType: _selectedType == FuelType.spbu ? 'spbu' : 'madura',
                amount: _selectedAmount,
                paymentSource: _paymentSource,
              );
              Navigator.pop(context);
            },
            child: Text('Simpan Pengeluaran (Rp $_selectedAmount)', style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Modal bottom sheet dapat menampilkan pilihan toggle SPBU Resmi vs Eceran Madura.
2. Nominal preset chip berubah dinamis sesuai jenis bahan bakar yang aktif (SPBU: kelipatan Rp5.000, Madura: kelipatan Rp12.000).
3. Callback `onSubmit` menerima tipe bensin dan nominal integer Rupiah yang tepat.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/features/quick_actions/presentation/quick_fuel_bottom_sheet_test.dart`
- **Metode Uji:**
  1. `testWidgets('Selecting Madura switches preset chips to 12k intervals', (tester) async)`:
     - Tampilkan `QuickFuelBottomSheet`.
     - Tap toggle 'Madura' $\rightarrow$ Verifikasi chip '1 Botol (Rp 12k)' tampil.
     - Tap tombol Simpan $\rightarrow$ Verifikasi argumen `fuelType: 'madura', amount: 12000`.
