import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/features/closing/data/closing_repository.dart';
import 'package:driver_finance_mobile/features/closing/presentation/controllers/closing_controller.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';

class MockClosingRepository extends Fake implements ClosingRepository {
  bool shouldThrow = false;
  int? finalizedWorkDayId;

  @override
  Future<Map<String, dynamic>> finalizeClosing(
    int workDayId,
    Map<int, int> denominations, {
    String? notes,
  }) async {
    if (shouldThrow) throw Exception('Server error closing');
    finalizedWorkDayId = workDayId;
    return {'status': 'success'};
  }
}

void main() {
  group('ClosingController Tests', () {
    late MockClosingRepository mockRepo;
    late ClosingController controller;

    setUp(() {
      mockRepo = MockClosingRepository();
      controller = ClosingController(
        repository: mockRepo,
        initialBookBalance: 100000,
      );
    });

    test('updateCount recalculates physical total and difference reactively', () {
      expect(controller.state.physicalTotal, 0);
      expect(controller.state.difference, -100000);
      expect(controller.state.isBalanced, false);

      // Input 1 lembar Rp100.000
      controller.updateCount(100000, 1);

      expect(controller.state.physicalTotal, 100000);
      expect(controller.state.difference, 0);
      expect(controller.state.isBalanced, true);
    });

    test('finalize rejects action if difference != 0', () async {
      // Masih selisih (0 vs 100000)
      final success = await controller.finalize(1);

      expect(success, false);
      expect(controller.state.isFinalizing, false);
      expect(controller.state.errorMessage, contains('Selisih wajib Rp0'));
      expect(mockRepo.finalizedWorkDayId, isNull);
    });

    test('finalize succeeds when cash is balanced', () async {
      controller.updateCount(50000, 2); // 100k, pas seimbang

      final success = await controller.finalize(1);

      expect(success, true);
      expect(controller.state.isFinalizing, false);
      expect(controller.state.successMessage, 'Closing hari kerja berhasil difinalisasi.');
      expect(mockRepo.finalizedWorkDayId, 1);
    });
  });
}
