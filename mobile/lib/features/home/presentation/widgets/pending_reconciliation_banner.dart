import 'package:flutter/material.dart';

class PendingReconciliationBanner extends StatelessWidget {
  final int pendingDaysCount;
  final VoidCallback onTap;

  const PendingReconciliationBanner({
    super.key,
    required this.pendingDaysCount,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    if (pendingDaysCount == 0) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF78350F).withOpacity(0.35), // Dark Amber
        border: Border.all(color: const Color(0xFFF59E0B).withOpacity(0.8)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber_rounded, color: Color(0xFFF59E0B), size: 28),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$pendingDaysCount Hari Kerja Perlu Rekonsiliasi',
                  style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 13),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Selisih belum klop. Anda tetap bisa narik hari ini.',
                  style: TextStyle(color: Color(0xFFD1D5DB), fontSize: 11),
                ),
              ],
            ),
          ),
          TextButton(
            onPressed: onTap,
            child: const Text('Audit', style: TextStyle(color: Color(0xFFF59E0B), fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
