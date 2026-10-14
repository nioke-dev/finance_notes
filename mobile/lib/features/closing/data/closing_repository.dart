import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'models/reconciliation_result_model.dart';

final closingRepositoryProvider = Provider<ClosingRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  return ClosingRepository(apiClient: client);
});

class ClosingRepository {
  final ApiClient apiClient;

  ClosingRepository({required this.apiClient});

  Future<List<Map<String, dynamic>>> getPendingReconciliations() async {
    final response = await apiClient.get('/reconciliation/unfinalized-dates');
    final data = response.data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return List<Map<String, dynamic>>.from(data['data']);
    }
    return [];
  }

  Future<int> getPendingReconciliationsCount() async {
    final list = await getPendingReconciliations();
    return list.length;
  }

  Future<Map<String, dynamic>> openWorkDay({String? date}) async {
    final response = await apiClient.post(
      '/work-days/open',
      data: date != null ? {'date': date} : {},
    );
    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }

  Future<ReconciliationResultModel> calculateDifference(
    int workDayId,
    Map<int, int> denominations,
  ) async {
    final formattedDenominations = denominations.entries
        .map((e) => {'nominal': e.key, 'quantity': e.value})
        .toList();

    final response = await apiClient.post(
      '/reconciliation/calculate-difference',
      data: {
        'work_day_id': workDayId,
        'denominations': formattedDenominations,
      },
    );

    final rawData = response.data is Map<String, dynamic> ? response.data : <String, dynamic>{};
    return ReconciliationResultModel.fromJson(rawData);
  }

  Future<Map<String, dynamic>> finalizeClosing(
    int workDayId,
    Map<int, int> denominations, {
    String? notes,
  }) async {
    final formattedDenominations = denominations.entries
        .map((e) => {'nominal': e.key, 'quantity': e.value})
        .toList();

    final response = await apiClient.post(
      '/reconciliation/finalize',
      data: {
        'work_day_id': workDayId,
        'denominations': formattedDenominations,
        if (notes != null) 'closing_notes': notes,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }
}
