import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'models/bonus_evaluation_model.dart';

final shopeeRepositoryProvider = Provider<ShopeeRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  return ShopeeRepository(apiClient: client);
});

class ShopeeRepository {
  final ApiClient apiClient;

  ShopeeRepository({required this.apiClient});

  Future<BonusEvaluationModel> evaluateBonus({int? workDayId}) async {
    final response = await apiClient.get(
      '/driver/evaluate-bonus',
      queryParameters: workDayId != null ? {'work_day_id': workDayId} : null,
    );

    final rawData = response.data is Map<String, dynamic> ? response.data : <String, dynamic>{};
    return BonusEvaluationModel.fromJson(rawData);
  }

  Future<Map<String, dynamic>> saveSnapshot({
    required int completedOrders,
    required int hubIncome,
    required int hubBonus,
    required int customerTip,
    required int shopeepayBalance,
    required int gopayBalance,
    required int gopayMerchantBalance,
  }) async {
    final response = await apiClient.post(
      '/driver/snapshots',
      data: {
        'completed_orders': completedOrders,
        'hub_income': hubIncome,
        'hub_bonus': hubBonus,
        'customer_tip': customerTip,
        'shopeepay_balance': shopeepayBalance,
        'gopay_balance': gopayBalance,
        'gopay_merchant_balance': gopayMerchantBalance,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }

  Future<Map<String, dynamic>> confirmShopeeBonusDisbursement({
    required int bonusAmount,
    required int destinationAccountId,
  }) async {
    final response = await apiClient.post(
      '/driver/confirm-bonus',
      data: {
        'bonus_amount': bonusAmount,
        'destination_account_id': destinationAccountId,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }
}
