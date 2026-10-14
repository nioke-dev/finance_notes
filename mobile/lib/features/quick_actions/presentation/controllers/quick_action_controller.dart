import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/quick_action_repository.dart';
import '../../../../core/services/location_service.dart';

class QuickActionState {
  final bool isLoading;
  final String? successMessage;
  final String? errorMessage;

  const QuickActionState({
    this.isLoading = false,
    this.successMessage,
    this.errorMessage,
  });

  QuickActionState copyWith({
    bool? isLoading,
    String? successMessage,
    String? errorMessage,
  }) {
    return QuickActionState(
      isLoading: isLoading ?? this.isLoading,
      successMessage: successMessage,
      errorMessage: errorMessage,
    );
  }
}

final quickActionControllerProvider =
    StateNotifierProvider<QuickActionController, QuickActionState>((ref) {
  final repository = ref.watch(quickActionRepositoryProvider);
  final locationService = ref.watch(locationServiceProvider);

  return QuickActionController(
    repository: repository,
    locationService: locationService,
  );
});

class QuickActionController extends StateNotifier<QuickActionState> {
  final QuickActionRepository repository;
  final LocationService locationService;

  QuickActionController({
    required this.repository,
    required this.locationService,
  }) : super(const QuickActionState());

  Future<bool> recordParking(int amount, {String? restaurantName}) async {
    state = state.copyWith(isLoading: true, successMessage: null, errorMessage: null);

    try {
      final loc = await locationService.getCurrentLocation();
      await repository.submitParking(
        amount: amount,
        latitude: loc?.latitude,
        longitude: loc?.longitude,
        restaurantName: restaurantName,
      );

      state = state.copyWith(
        isLoading: false,
        successMessage: 'Parkir Rp $amount tercatat',
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

  Future<bool> recordFuel({
    required String vendorType,
    required int amount,
    int? sourceAccountId,
  }) async {
    state = state.copyWith(isLoading: true, successMessage: null, errorMessage: null);

    try {
      await repository.submitFuel(
        vendorType: vendorType,
        amount: amount,
        sourceAccountId: sourceAccountId,
      );

      state = state.copyWith(
        isLoading: false,
        successMessage: 'Bensin Rp $amount berhasil disimpan',
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

  Future<bool> recordCod({
    required int orderAmount,
    required int cashCollected,
    String? orderNumber,
  }) async {
    state = state.copyWith(isLoading: true, successMessage: null, errorMessage: null);

    try {
      await repository.submitCod(
        orderAmount: orderAmount,
        cashCollected: cashCollected,
        orderNumber: orderNumber,
      );

      state = state.copyWith(
        isLoading: false,
        successMessage: 'COD Rp $orderAmount berhasil diverifikasi',
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
