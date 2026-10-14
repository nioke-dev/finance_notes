import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/features/shopee/data/shopee_repository.dart';
import 'package:driver_finance_mobile/features/shopee/data/models/bonus_evaluation_model.dart';
import 'package:driver_finance_mobile/features/shopee/presentation/controllers/shopee_controller.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';

class MockShopeeRepository extends Fake implements ShopeeRepository {
  bool shouldThrow = false;
  BonusEvaluationModel mockEvaluation = const BonusEvaluationModel(
    isEligible: true,
    bonusAmount: 37500,
    completedOrders: 23,
    onlineHours: 9.8,
  );

  @override
  Future<BonusEvaluationModel> evaluateBonus({int? workDayId}) async {
    if (shouldThrow) throw Exception('Gagal menghubungi server');
    return mockEvaluation;
  }

  @override
  Future<Map<String, dynamic>> saveSnapshot({
    required int completedOrders,
    required int hubIncome,
    required int hubBonus,
    required int customerTip,
    required int shopeepayBalance,
    required int gopayBalance,
    required int gopayMerchantBalance,
  }) async {
    if (shouldThrow) throw Exception('Gagal simpan snapshot');
    return {'status': 'success'};
  }
}

void main() {
  group('ShopeeController Tests', () {
    late MockShopeeRepository mockRepo;
    late ShopeeController controller;

    setUp(() {
      mockRepo = MockShopeeRepository();
      controller = ShopeeController(repository: mockRepo);
    });

    test('checkBonusCutOff updates evaluation state from repository', () async {
      await controller.checkBonusCutOff();

      expect(controller.state.isLoading, false);
      expect(controller.state.evaluation, isNotNull);
      expect(controller.state.evaluation?.isEligible, true);
      expect(controller.state.evaluation?.bonusAmount, 37500);
      expect(controller.state.errorMessage, isNull);
    });

    test('updateSnapshot updates local state numbers correctly', () async {
      final success = await controller.updateSnapshot(
        completedOrders: 16,
        hubIncome: 128000,
        hubBonus: 16000,
        customerTip: 10000,
        shopeepayBalance: 200000,
        gopayBalance: 40000,
        gopayMerchantBalance: 20000,
      );

      expect(success, true);
      expect(controller.state.completedOrders, 16);
      expect(controller.state.hubIncome, 128000);
      expect(controller.state.hubBonus, 16000);
      expect(controller.state.successMessage, 'Snapshot progres tersimpan');
    });

    test('checkBonusCutOff handles network failure gracefully', () async {
      mockRepo.shouldThrow = true;

      await controller.checkBonusCutOff();

      expect(controller.state.isLoading, false);
      expect(controller.state.errorMessage, contains('Gagal menghubungi server'));
    });
  });
}
