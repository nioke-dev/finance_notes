import 'package:flutter_test/flutter_test.dart';
import 'package:dio/dio.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';
import 'package:driver_finance_mobile/features/quick_actions/data/quick_action_repository.dart';

class MockDio extends Fake implements Dio {
  String? capturedPath;
  dynamic capturedData;

  @override
  Interceptors get interceptors => Interceptors();

  @override
  BaseOptions get options => BaseOptions();

  @override
  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    capturedPath = path;
    capturedData = data;

    return Response<T>(
      requestOptions: RequestOptions(path: path),
      data: {'status': 'success'} as T,
      statusCode: 200,
    );
  }
}

void main() {
  group('QuickActionRepository Tests', () {
    late MockDio mockDio;
    late ApiClient apiClient;
    late QuickActionRepository repository;

    setUp(() {
      mockDio = MockDio();
      apiClient = ApiClient(customDio: mockDio);
      repository = QuickActionRepository(apiClient: apiClient);
    });

    test('submitParking sends expected payload to /quick-actions/parking', () async {
      await repository.submitParking(
        amount: 2000,
        latitude: -7.9826,
        longitude: 112.6308,
        restaurantName: 'Ayam Geprek Kak Rose',
      );

      expect(mockDio.capturedPath, '/quick-actions/parking');
      expect(mockDio.capturedData, {
        'amount': 2000,
        'latitude': -7.9826,
        'longitude': 112.6308,
        'restaurant_name': 'Ayam Geprek Kak Rose',
      });
    });

    test('submitFuel sends expected payload to /quick-actions/fuel', () async {
      await repository.submitFuel(
        vendorType: 'madura',
        amount: 12000,
      );

      expect(mockDio.capturedPath, '/quick-actions/fuel');
      expect(mockDio.capturedData, {
        'vendor_type': 'madura',
        'amount': 12000,
      });
    });

    test('submitCod sends expected payload to /quick-actions/cod', () async {
      await repository.submitCod(
        orderAmount: 35000,
        cashCollected: 40000,
        orderNumber: 'SHP-99881',
      );

      expect(mockDio.capturedPath, '/quick-actions/cod');
      expect(mockDio.capturedData, {
        'order_amount': 35000,
        'cash_collected': 40000,
        'order_number': 'SHP-99881',
      });
    });
  });
}
