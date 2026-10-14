import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import 'widgets/pending_reconciliation_banner.dart';
import 'widgets/quick_balance_card.dart';

class HomeScreen extends ConsumerWidget {
  final int pendingDaysCount;
  final int cashInWallet;
  final int shopeepayBalance;
  final int gopayMerchantBalance;
  final bool isShiftOpen;
  final int completedOrders;
  final VoidCallback? onOpenShift;
  final VoidCallback? onAuditTap;

  const HomeScreen({
    super.key,
    this.pendingDaysCount = 0,
    this.cashInWallet = 0,
    this.shopeepayBalance = 0,
    this.gopayMerchantBalance = 0,
    this.isShiftOpen = false,
    this.completedOrders = 0,
    this.onOpenShift,
    this.onAuditTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppTheme.slate900,
      appBar: AppBar(
        title: const Text('DRIVER FINANCE MALANG'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
            onPressed: () {},
          ),
        ],
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.symmetric(vertical: 8),
          children: [
            // Banner Hari Belum Rekonsiliasi (Hari Gantung)
            PendingReconciliationBanner(
              pendingDaysCount: pendingDaysCount,
              onTap: onAuditTap ?? () {},
            ),

            // Saldo Kas & Dompet Cepat
            QuickBalanceCard(
              cashInWallet: cashInWallet,
              shopeepayBalance: shopeepayBalance,
              gopayMerchantBalance: gopayMerchantBalance,
            ),

            // Status Shift Kerja Hari Ini
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: isShiftOpen
                  ? Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Icon(Icons.circle, color: Color(0xFF10B981), size: 10),
                                SizedBox(width: 6),
                                Text('STATUS: ON-BEAT AKTIF', style: TextStyle(color: Color(0xFF10B981), fontSize: 11, fontWeight: FontWeight.bold)),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text('$completedOrders Trip Diselesaikan', style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFF10B981).withOpacity(0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Text('Sedang Narik', style: TextStyle(color: Color(0xFF10B981), fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    )
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Hari kerja belum dibuka',
                          style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 10),
                        ElevatedButton.icon(
                          onPressed: onOpenShift,
                          icon: const Icon(Icons.two_wheeler_rounded),
                          label: const Text('BUKA SHIFT HARI INI'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF10B981),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                        ),
                      ],
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
