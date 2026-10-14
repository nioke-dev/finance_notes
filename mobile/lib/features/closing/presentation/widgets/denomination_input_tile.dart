import 'package:flutter/material.dart';

class DenominationInputTile extends StatelessWidget {
  final int denomination;
  final int count;
  final ValueChanged<int> onChanged;

  const DenominationInputTile({
    super.key,
    required this.denomination,
    required this.count,
    required this.onChanged,
  });

  String _formatDenomination(int value) {
    if (value >= 1000) {
      return 'Rp ${(value / 1000).toInt()}k';
    }
    return 'Rp $value';
  }

  @override
  Widget build(BuildContext context) {
    final subtotal = denomination * count;

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 4),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Row(
        children: [
          // Label pecahan
          SizedBox(
            width: 80,
            child: Text(
              _formatDenomination(denomination),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
          ),
          // Tombol -
          IconButton(
            icon: const Icon(Icons.remove_circle_outline, color: Color(0xFF94A3B8)),
            onPressed: count > 0 ? () => onChanged(count - 1) : null,
          ),
          // Lembar text
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              '$count lbr',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
          ),
          // Tombol +
          IconButton(
            icon: const Icon(Icons.add_circle_outline, color: Color(0xFF10B981)),
            onPressed: () => onChanged(count + 1),
          ),
          const Spacer(),
          // Subtotal
          Text(
            'Rp ${subtotal.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}',
            style: const TextStyle(
              color: Color(0xFF94A3B8),
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
