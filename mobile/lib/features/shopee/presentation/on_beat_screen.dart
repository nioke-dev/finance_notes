import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:driver_finance_mobile/features/shopee/presentation/controllers/shopee_controller.dart';
import 'package:driver_finance_mobile/features/shopee/presentation/widgets/bonus_cut_off_dialog.dart';

class OnBeatScreen extends ConsumerStatefulWidget {
  const OnBeatScreen({super.key});

  @override
  ConsumerState<OnBeatScreen> createState() => _OnBeatScreenState();
}

class _OnBeatScreenState extends ConsumerState<OnBeatScreen> {
  final _tripsController = TextEditingController();
  final _hoursController = TextEditingController();

  @override
  void dispose() {
    _tripsController.dispose();
    _hoursController.dispose();
    super.dispose();
  }

  void _showEvaluationDialog() async {
    final controller = ref.read(shopeeControllerProvider.notifier);
    final evaluation = await controller.evaluateBonusCutOff();
    if (!mounted || evaluation == null) return;

    showDialog(
      context: context,
      builder: (ctx) => BonusCutOffDialog(
        isEligible: evaluation.isEligible,
        bonusAmount: evaluation.bonusAmount,
        message: evaluation.message,
        onDismiss: () => Navigator.pop(ctx),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final shopeeState = ref.watch(shopeeControllerProvider);

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        title: const Text('On-Beat Shopee Tracker', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF1E293B),
        elevation: 0,
        actions: [
          IconButton(
            tooltip: 'Evaluasi Jam 20.00',
            icon: const Icon(Icons.analytics_outlined, color: Color(0xFFEE4D2D)),
            onPressed: _showEvaluationDialog,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // KPI Summary Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildMetric(
                        label: 'Total Trips',
                        value: '${shopeeState.totalTrips}',
                        icon: Icons.delivery_dining_rounded,
                        color: const Color(0xFFEE4D2D),
                      ),
                      Container(height: 40, width: 1, color: const Color(0xFF334155)),
                      _buildMetric(
                        label: 'Jam Online',
                        value: '${shopeeState.onlineHours}h',
                        icon: Icons.timer_outlined,
                        color: const Color(0xFF38BDF8),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  // Tier Progress Bar (Target Tier 1: 16, Tier 2: 21, Tier 3: 26)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Target Bonus Malang (Tier 1: 16 Trip)', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                          Text('${(shopeeState.totalTrips / 16 * 100).clamp(0, 100).toInt()}%', style: const TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 12)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: (shopeeState.totalTrips / 16).clamp(0.0, 1.0),
                          backgroundColor: const Color(0xFF0F172A),
                          color: const Color(0xFF10B981),
                          minHeight: 10,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Form Catat Snapshot Cepat
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Update Snapshot Trip Terkini', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _tripsController,
                    keyboardType: TextInputType.number,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      labelText: 'Jumlah Trip Saat Ini',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF0F172A),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _hoursController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      labelText: 'Total Jam Online (cth: 5.5)',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF0F172A),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFEE4D2D),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () async {
                        final trips = int.tryParse(_tripsController.text);
                        final hours = double.tryParse(_hoursController.text);
                        if (trips != null && hours != null) {
                          await ref.read(shopeeControllerProvider.notifier).recordTripSnapshot(
                            trips: trips,
                            onlineHours: hours,
                          );
                          _tripsController.clear();
                          _hoursController.clear();
                        }
                      },
                      child: const Text('Kirim Snapshot Progres', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
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

  Widget _buildMetric({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Column(
      children: [
        Icon(icon, color: color, size: 28),
        const SizedBox(height: 6),
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
      ],
    );
  }
}
