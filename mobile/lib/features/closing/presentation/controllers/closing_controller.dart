import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/closing_repository.dart';

class ClosingState {
  final int bookBalance;
  final Map<int, int> denominations;
  final bool isFinalizing;
  final String? errorMessage;
  final String? successMessage;

  const ClosingState({
    required this.bookBalance,
    required this.denominations,
    this.isFinalizing = false,
    this.errorMessage,
    this.successMessage,
  });

  int get physicalTotal =>
      denominations.entries.fold(0, (sum, e) => sum + (e.key * e.value));

  int get difference => physicalTotal - bookBalance;

  bool get isBalanced => difference == 0;

  ClosingState copyWith({
    int? bookBalance,
    Map<int, int>? denominations,
    bool? isFinalizing,
    String? errorMessage,
    String? successMessage,
  }) {
    return ClosingState(
      bookBalance: bookBalance ?? this.bookBalance,
      denominations: denominations ?? this.denominations,
      isFinalizing: isFinalizing ?? this.isFinalizing,
      errorMessage: errorMessage,
      successMessage: successMessage,
    );
  }
}

final closingControllerProvider = StateNotifierProvider.family<
    ClosingController, ClosingState, int>((ref, initialBookBalance) {
  final repository = ref.watch(closingRepositoryProvider);
  return ClosingController(
    repository: repository,
    initialBookBalance: initialBookBalance,
  );
});

class ClosingController extends StateNotifier<ClosingState> {
  final ClosingRepository repository;

  ClosingController({
    required this.repository,
    required int initialBookBalance,
  }) : super(ClosingState(bookBalance: initialBookBalance, denominations: const {}));

  void updateCount(int nominal, int quantity) {
    final updated = Map<int, int>.from(state.denominations);
    if (quantity <= 0) {
      updated.remove(nominal);
    } else {
      updated[nominal] = quantity;
    }

    state = state.copyWith(
      denominations: updated,
      errorMessage: null,
      successMessage: null,
    );
  }

  void setBookBalance(int balance) {
    state = state.copyWith(bookBalance: balance);
  }

  Future<bool> finalize(int workDayId, {String? notes}) async {
    if (!state.isBalanced) {
      state = state.copyWith(
        errorMessage: 'Closing gagal: Kas fisik selisih Rp ${state.difference.abs()}. Selisih wajib Rp0.',
      );
      return false;
    }

    state = state.copyWith(isFinalizing: true, errorMessage: null);

    try {
      await repository.finalizeClosing(
        workDayId,
        state.denominations,
        notes: notes,
      );

      state = state.copyWith(
        isFinalizing: false,
        successMessage: 'Closing hari kerja berhasil difinalisasi.',
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isFinalizing: false,
        errorMessage: e.toString(),
      );
      return false;
    }
  }
}
