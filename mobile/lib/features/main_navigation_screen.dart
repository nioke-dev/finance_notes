import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/services/update_service.dart';
import '../core/theme/app_theme.dart';
import 'closing/presentation/closing_screen.dart';
import 'debts/presentation/debts_screen.dart';
import 'home/presentation/home_screen.dart';
import 'shopee/presentation/on_beat_screen.dart';
import 'voice_memo/presentation/controllers/voice_memo_controller.dart';
import 'voice_memo/presentation/widgets/voice_memo_fab.dart';

class MainNavigationScreen extends ConsumerStatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  ConsumerState<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends ConsumerState<MainNavigationScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkAppUpdate();
    });
  }

  Future<void> _checkAppUpdate() async {
    final updateService = ref.read(updateServiceProvider);
    final updateInfo = await updateService.checkForUpdate(currentVersionCode: 1);
    if (mounted && updateInfo != null && updateInfo.hasUpdate) {
      UpdateService.showUpdateDialog(context, updateInfo);
    }
  }

  @override
  Widget build(BuildContext context) {
    final voiceMemoNotifier = ref.read(voiceMemoControllerProvider.notifier);

    final screens = [
      HomeScreen(
        pendingDaysCount: 0,
        cashInWallet: 185000,
        shopeepayBalance: 320000,
        gopayMerchantBalance: 45000,
        isShiftOpen: true,
        completedOrders: 14,
        onOpenShift: () {
          setState(() => _currentIndex = 1);
        },
      ),
      const OnBeatScreen(),
      const DebtsScreen(),
      ClosingScreen(
        bookBalance: 185000,
        onFinalize: (denoms) async {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Closing shift berhasil difinalisasi!')),
          );
        },
      ),
    ];

    return Scaffold(
      backgroundColor: AppTheme.slate900,
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: VoiceMemoFab(
        onStartRecord: () async {
          await voiceMemoNotifier.startRecording();
        },
        onStopRecord: () async {
          await voiceMemoNotifier.stopAndUpload();
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                backgroundColor: Color(0xFF10B981),
                content: Text('🎙️ Memo suara tersimpan di Kotak Hitam!'),
              ),
            );
          }
        },
      ),
      bottomNavigationBar: BottomAppBar(
        shape: const CircularNotchedRectangle(),
        notchMargin: 8,
        color: const Color(0xFF1E293B),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _buildNavItem(Icons.dashboard_rounded, 'Dashboard', 0),
            _buildNavItem(Icons.two_wheeler_rounded, 'On-Beat', 1),
            const SizedBox(width: 48), // Spacer for notched VoiceMemoFab
            _buildNavItem(Icons.credit_card_rounded, 'Utang', 2),
            _buildNavItem(Icons.lock_clock_rounded, 'Closing', 3),
          ],
        ),
      ),
    );
  }

  Widget _buildNavItem(IconData icon, String label, int index) {
    final isSelected = _currentIndex == index;
    final color = isSelected ? const Color(0xFF10B981) : const Color(0xFF94A3B8);

    return InkWell(
      onTap: () => setState(() => _currentIndex = index),
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
