import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/shopee_repository.dart';
import '../../data/models/bonus_evaluation_model.dart';

class ShopeeState {
  final bool isLoading;
  final int completedOrders;
  final int hubIncome;
  final int hubBonus;
  final int customerTip;
  final BonusEvaluationModel? evaluation;
  final String? successMessage;
  final String? errorMessage;

  const ShopeeState({
    this.isLoading = false,
    this.completedOrders = 0,
    this.hubIncome = 0,
    this.hubBonus = 0,
    this.customerTip = 0,
    this.evaluation,
    this.successMessage,
    this.errorMessage,
  });

  ShopeeState copyWith({
    bool? isLoading,
    int? completedOrders,
    int? hubIncome,
    int? hubBonus,
    int? customerTip,
    BonusEvaluationModel? evaluation,
    String? successMessage,
    String? errorMessage,
  }) {
    return ShopeeState(
      isLoading: isLoading ?? this.isLoading,
      completedOrders: completedOrders ?? this.completedOrders,
      hubIncome: hubIncome ?? this.hubIncome,
      hubBonus: hubBonus ?? this.hubBonus,
      customerTip: customerTip ?? this.customerTip,
      evaluation: evaluation ?? this.evaluation,
      successMessage: successMessage,
      errorMessage: errorMessage,
    );
  }

  int get totalTrips => completedOrders;
  int get onlineHours => 8;
}

final shopeeControllerProvider =
    StateNotifierProvider<ShopeeController, ShopeeState>((ref) {
  final repository = ref.watch(shopeeRepositoryProvider);
  return ShopeeController(repository: repository);
});

class ShopeeController extends StateNotifier<ShopeeState> {
  final ShopeeRepository repository;

  ShopeeController({required this.repository}) : super(const ShopeeState());

  Future<void> checkBonusCutOff({int? workDayId}) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final eval = await repository.evaluateBonus(workDayId: workDayId);
      state = state.copyWith(
        isLoading: false,
        evaluation: eval,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<BonusEvaluationModel?> evaluateBonusCutOff({int? workDayId}) async {
    await checkBonusCutOff(workDayId: workDayId);
    return state.evaluation;
  }

  Future<bool> recordTripSnapshot({required int trips, required int income}) async {
    return updateSnapshot(
      completedOrders: trips,
      hubIncome: income,
      hubBonus: 0,
      customerTip: 0,
      shopeepayBalance: 0,
      gopayBalance: 0,
      gopayMerchantBalance: 0,
    );
  }

  Future<bool> updateSnapshot({
    required int completedOrders,
    required int hubIncome,
    required int hubBonus,
    required int customerTip,
    required int shopeepayBalance,
    required int gopayBalance,
    required int gopayMerchantBalance,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      await repository.saveSnapshot(
        completedOrders: completedOrders,
        hubIncome: hubIncome,
        hubBonus: hubBonus,
        customerTip: customerTip,
        shopeepayBalance: shopeepayBalance,
        gopayBalance: gopayBalance,
        gopayMerchantBalance: gopayMerchantBalance,
      );

      state = state.copyWith(
        isLoading: false,
        completedOrders: completedOrders,
        hubIncome: hubIncome,
        hubBonus: hubBonus,
        customerTip: customerTip,
        successMessage: 'Snapshot progres tersimpan',
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  Future<bool> confirmBonusDisbursement({
    required int bonusAmount,
    required int destinationAccountId,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      await repository.confirmShopeeBonusDisbursement(
        bonusAmount: bonusAmount,
        destinationAccountId: destinationAccountId,
      );

      state = state.copyWith(
        isLoading: false,
        successMessage: 'Pencairan bonus Rp $bonusAmount berhasil dikonfirmasi',
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }
}
