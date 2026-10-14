import 'package:flutter/material.dart';

enum FuelType { spbu, madura }

class QuickFuelBottomSheet extends StatefulWidget {
  final Future<void> Function({
    required String fuelType,
    required int amount,
    required String paymentSource,
  }) onSubmit;

  const QuickFuelBottomSheet({super.key, required this.onSubmit});

  @override
  State<QuickFuelBottomSheet> createState() => _QuickFuelBottomSheetState();
}

class _QuickFuelBottomSheetState extends State<QuickFuelBottomSheet> {
  FuelType _selectedType = FuelType.spbu;
  int _selectedAmount = 20000;
  String _paymentSource = 'cash';
  bool _isSubmitting = false;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: const BoxDecoration(
        color: Color(0xFF1E293B), // Slate 800
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Catat Bensin Motor',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
            const SizedBox(height: 14),

            // Toggle Switch SPBU vs Madura
            SegmentedButton<FuelType>(
              style: ButtonStyle(
                backgroundColor: MaterialStateProperty.resolveWith<Color>((states) {
                  if (states.contains(MaterialState.selected)) {
                    return _selectedType == FuelType.spbu
                        ? const Color(0xFF10B981)
                        : const Color(0xFFF59E0B);
                  }
                  return const Color(0xFF0F172A);
                }),
                foregroundColor: MaterialStateProperty.all(Colors.white),
              ),
              segments: const [
                ButtonSegment(
                  value: FuelType.spbu,
                  label: Text('SPBU Resmi (10k/L)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                ),
                ButtonSegment(
                  value: FuelType.madura,
                  label: Text('Madura (12k/Btl)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                ),
              ],
              selected: {_selectedType},
              onSelectionChanged: (set) => setState(() {
                _selectedType = set.first;
                _selectedAmount = _selectedType == FuelType.spbu ? 20000 : 12000;
              }),
            ),
            const SizedBox(height: 16),

            // Chips Nominal Bensin
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _selectedType == FuelType.spbu
                  ? [15000, 20000, 25000, 30000].map((amt) {
                      final liters = (amt / 10000).toStringAsFixed(1);
                      return ChoiceChip(
                        label: Text('Rp ${(amt / 1000).toInt()}k (~$liters L)'),
                        selected: _selectedAmount == amt,
                        selectedColor: const Color(0xFF10B981).withOpacity(0.3),
                        labelStyle: TextStyle(
                          color: _selectedAmount == amt ? const Color(0xFF10B981) : Colors.white70,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (val) => setState(() => _selectedAmount = amt),
                      );
                    }).toList()
                  : [12000, 24000].map((amt) {
                      final bottles = amt ~/ 12000;
                      return ChoiceChip(
                        label: Text('$bottles Botol (Rp ${(amt / 1000).toInt()}k)'),
                        selected: _selectedAmount == amt,
                        selectedColor: const Color(0xFFF59E0B).withOpacity(0.3),
                        labelStyle: TextStyle(
                          color: _selectedAmount == amt ? const Color(0xFFF59E0B) : Colors.white70,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (val) => setState(() => _selectedAmount = amt),
                      );
                    }).toList(),
            ),
            const SizedBox(height: 24),

            // Tombol Simpan
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: _selectedType == FuelType.spbu
                    ? const Color(0xFF10B981)
                    : const Color(0xFFF59E0B),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              onPressed: _isSubmitting
                  ? null
                  : () async {
                      setState(() => _isSubmitting = true);
                      await widget.onSubmit(
                        fuelType: _selectedType == FuelType.spbu ? 'spbu' : 'madura',
                        amount: _selectedAmount,
                        paymentSource: _paymentSource,
                      );
                      if (mounted) {
                        Navigator.pop(context);
                      }
                    },
              child: _isSubmitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(
                      'Simpan Pengeluaran (Rp ${_selectedAmount.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')})',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
