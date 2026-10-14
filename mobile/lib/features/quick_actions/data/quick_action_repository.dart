import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final quickActionRepositoryProvider = Provider<QuickActionRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  return QuickActionRepository(apiClient: client);
});

class QuickActionRepository {
  final ApiClient apiClient;

  QuickActionRepository({required this.apiClient});

  Future<Map<String, dynamic>> submitParking({
    required int amount,
    double? latitude,
    double? longitude,
    String? restaurantName,
  }) async {
    final response = await apiClient.post(
      '/quick-actions/parking',
      data: {
        'amount': amount,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
        if (restaurantName != null) 'restaurant_name': restaurantName,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }

  Future<Map<String, dynamic>> submitFuel({
    required String vendorType,
    required int amount,
    int? sourceAccountId,
  }) async {
    final response = await apiClient.post(
      '/quick-actions/fuel',
      data: {
        'vendor_type': vendorType,
        'amount': amount,
        if (sourceAccountId != null) 'source_account_id': sourceAccountId,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }

  Future<Map<String, dynamic>> submitCod({
    required int orderAmount,
    required int cashCollected,
    String? orderNumber,
  }) async {
    final response = await apiClient.post(
      '/quick-actions/cod',
      data: {
        'order_amount': orderAmount,
        'cash_collected': cashCollected,
        if (orderNumber != null) 'order_number': orderNumber,
      },
    );

    return response.data is Map<String, dynamic> ? response.data : {'status': 'success'};
  }
}
