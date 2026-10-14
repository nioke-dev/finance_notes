import 'package:flutter/material.dart';

class QuickBalanceCard extends StatelessWidget {
  final int cashInWallet;
  final int shopeepayBalance;
  final int gopayMerchantBalance;

  const QuickBalanceCard({
    super.key,
    required this.cashInWallet,
    required this.shopeepayBalance,
    required this.gopayMerchantBalance,
  });

  String _formatRp(int amount) {
    return 'Rp ${amount.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B), // Slate 800
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'KAS FISIK DI DOMPET',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              letterSpacing: 1.2,
              color: Color(0xFF94A3B8),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _formatRp(cashInWallet),
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w900,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 14),
          const Divider(color: Color(0xFF334155), height: 1),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('ShopeePay Driver', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                  const SizedBox(height: 2),
                  Text(_formatRp(shopeepayBalance), style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFFEE4D2D))),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text('GoPay Merchant', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                  const SizedBox(height: 2),
                  Text(_formatRp(gopayMerchantBalance), style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF10B981))),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
