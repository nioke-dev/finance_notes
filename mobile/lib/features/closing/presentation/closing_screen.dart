import 'package:flutter/material.dart';
import 'package:mobile/features/closing/presentation/widgets/denomination_input_tile.dart';

class ClosingScreen extends StatefulWidget {
  final int bookBalance;
  final Future<void> Function(Map<int, int> denominations) onFinalize;

  const ClosingScreen({
    super.key,
    required this.bookBalance,
    required this.onFinalize,
  });

  @override
  State<ClosingScreen> createState() => _ClosingScreenState();
}

class _ClosingScreenState extends State<ClosingScreen> {
  final Map<int, int> _counts = {
    100000: 0,
    50000: 0,
    20000: 0,
    10000: 0,
    5000: 0,
    2000: 0,
    1000: 0,
    500: 0,
  };

  bool _isFinalizing = false;

  int get _actualPhysicalTotal {
    return _counts.entries.fold(0, (sum, entry) => sum + (entry.key * entry.value));
  }

  int get _difference => _actualPhysicalTotal - widget.bookBalance;

  String _formatCurrency(int amount) {
    final abs = amount.abs().toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (m) => '${m[1]}.',
        );
    return amount < 0 ? '-Rp $abs' : 'Rp $abs';
  }

  @override
  Widget build(BuildContext context) {
    final isBalanced = _difference == 0;

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        title: const Text('Closing Shift Harian', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF1E293B),
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Ringkasan Saldo Buku
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFF334155)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Saldo Kas Buku:', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 14)),
                Text(
                  _formatCurrency(widget.bookBalance),
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Total Fisik Aktual
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFF334155)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Total Fisik Aktual:', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 14)),
                Text(
                  _formatCurrency(_actualPhysicalTotal),
                  style: const TextStyle(color: Color(0xFF38BDF8), fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Status & Selisih
          Container(
            padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
            decoration: BoxDecoration(
              color: isBalanced ? const Color(0xFF064E3B) : const Color(0xFF7F1D1D),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isBalanced ? const Color(0xFF10B981) : const Color(0xFFEF4444),
              ),
            ),
            child: Center(
              child: Text(
                isBalanced ? 'STATUS: KLOP (Rp 0)' : 'SELISIH: ${_formatCurrency(_difference)}',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),

          const Text(
            'Denominasi Pecahan Fisik:',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
          ),
          const SizedBox(height: 8),

          // List Pecahan
          ..._counts.keys.map((denom) {
            return DenominationInputTile(
              denomination: denom,
              count: _counts[denom] ?? 0,
              onChanged: (newCount) {
                setState(() {
                  _counts[denom] = newCount;
                });
              },
            );
          }),

          const SizedBox(height: 24),

          // Tombol Finalisasi (Hanya aktif jika klop)
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: isBalanced ? const Color(0xFF10B981) : Colors.grey.shade800,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: (isBalanced && !_isFinalizing)
                ? () async {
                    setState(() => _isFinalizing = true);
                    await widget.onFinalize(_counts);
                    if (mounted) {
                      setState(() => _isFinalizing = false);
                    }
                  }
                : null,
            child: _isFinalizing
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Text('Finalisasi Tutup Hari', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          ),
        ],
      ),
    );
  }
}
