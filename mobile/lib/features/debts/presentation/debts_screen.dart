import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class DebtsScreen extends ConsumerWidget {
  const DebtsScreen({super.key});

  String _formatCurrency(int amount) {
    final abs = amount.abs().toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (m) => '${m[1]}.',
        );
    return 'Rp $abs';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        title: const Text('Misi Bebas Hutang & Tagihan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF1E293B),
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Banner Misi Bebas Hutang 31 Oktober
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF311042), Color(0xFF1E293B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFA855F7).withOpacity(0.4)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFA855F7).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFA855F7).withOpacity(0.5)),
                      ),
                      child: const Row(
                        children: [
                          Text('🎯 ', style: TextStyle(fontSize: 12)),
                          Text(
                            'TARGET 31 OKTOBER',
                            style: TextStyle(color: Color(0xFFD8B4FE), fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                    const Text('Sisa 17 Hari', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text(
                  'Kuota Setoran Laba Bersih Driver:',
                  style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 12),
                ),
                const SizedBox(height: 4),
                Text(
                  '${_formatCurrency(85000)} / hari',
                  style: const TextStyle(color: Color(0xFFFACC15), fontSize: 24, fontWeight: FontWeight.black),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Setor kuota laba harian dari hasil on-bid agar seluruh tagihan bulan ini lunas sebelum tanggal 31.',
                  style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Daftar Platform Hutang Aktif
          const Text(
            'Daftar Platform Hutang & Cicilan',
            style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),

          _buildDebtCard('Shopee PayLater (SPayLater)', 'Jatuh Tempo: 25 Okt', 450000, 3, const Color(0xFFEE4D2D)),
          _buildDebtCard('TikTok PayLater', 'Jatuh Tempo: 31 Okt', 280000, 1, const Color(0xFF00F2FE)),
          _buildDebtCard('SeaBank Pinjam', 'Jatuh Tempo: 20 Okt', 500000, 6, const Color(0xFFF97316)),
          _buildDebtCard('SPinjam (Shopee Pinjam)', 'Jatuh Tempo: 05 Nov', 350000, 3, const Color(0xFFEAB308)),
          _buildDebtCard('GoPay Pinjam', 'Jatuh Tempo: 28 Okt', 200000, 1, const Color(0xFF10B981)),

          const SizedBox(height: 20),

          // Tagihan Langganan Bulanan
          const Text(
            'Langganan Rutin Bulanan',
            style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),

          _buildSubscriptionTile('Paket Data Kuota Narik (Telkomsel)', 75000, 'Tiap tgl 20', true),
          _buildSubscriptionTile('Sewa Kamar Kos', 600000, 'Tiap tgl 01', false),
          _buildSubscriptionTile('Spotify Premium Driver', 35000, 'Tiap tgl 15', true),
        ],
      ),
    );
  }

  Widget _buildDebtCard(String name, String dueDate, int remainingAmount, int tenor, Color accentColor) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                width: 4,
                height: 38,
                decoration: BoxDecoration(
                  color: accentColor,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                  const SizedBox(height: 2),
                  Text('$dueDate • Tenor $tenor bln', style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                ],
              ),
            ],
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(_formatCurrency(remainingAmount), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 2),
              const Text('Sisa Pokok', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 10)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSubscriptionTile(String name, int amount, String schedule, bool isPaid) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
              const SizedBox(height: 2),
              Text(schedule, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
            ],
          ),
          Row(
            children: [
              Text(_formatCurrency(amount), style: const TextStyle(color: Color(0xFFCBD5E1), fontWeight: FontWeight.bold, fontSize: 12)),
              const SizedBox(width: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isPaid ? const Color(0xFF10B981).withOpacity(0.15) : const Color(0xFFF59E0B).withOpacity(0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  isPaid ? 'Lunas' : 'Belum',
                  style: TextStyle(
                    color: isPaid ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                    fontWeight: FontWeight.bold,
                    fontSize: 10,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
