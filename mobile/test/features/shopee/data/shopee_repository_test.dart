import 'package:flutter_test/flutter_test.dart';
import 'package:dio/dio.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';
import 'package:driver_finance_mobile/features/shopee/data/shopee_repository.dart';
import 'package:driver_finance_mobile/features/shopee/data/models/bonus_evaluation_model.dart';

class MockDioShopee extends Fake implements Dio {
  String? capturedPath;
  dynamic capturedData;
  Map<String, dynamic>? capturedQueryParams;

  @override
  Interceptors get interceptors => Interceptors();

  @override
  BaseOptions get options => BaseOptions();

  @override
  Future<Response<T>> get<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onReceiveProgress,
  }) async {
    capturedPath = path;
    capturedQueryParams = queryParameters;

    final fakeResponse = {
      'status': 'success',
      'data': {
        'is_eligible': true,
        'bonus_amount': 37500,
        'completed_orders': 22,
        'online_hours': 9.5,
        'reason': 'Memenuhi syarat target 22 order dan 9.5 jam online',
      }
    };

    return Response<T>(
      requestOptions: RequestOptions(path: path),
      data: fakeResponse as T,
      statusCode: 200,
    );
  }

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
  group('ShopeeRepository Tests', () {
    late MockDioShopee mockDio;
    late ApiClient apiClient;
    late ShopeeRepository repository;

    setUp(() {
      mockDio = MockDioShopee();
      apiClient = ApiClient(customDio: mockDio);
      repository = ShopeeRepository(apiClient: apiClient);
    });

    test('evaluateBonus returns parsed BonusEvaluationModel', () async {
      final result = await repository.evaluateBonus(workDayId: 10);

      expect(mockDio.capturedPath, '/driver/evaluate-bonus');
      expect(mockDio.capturedQueryParams, {'work_day_id': 10});
      expect(result.isEligible, true);
      expect(result.bonusAmount, 37500);
      expect(result.completedOrders, 22);
      expect(result.onlineHours, 9.5);
    });

    test('saveSnapshot sends expected parameters', () async {
      await repository.saveSnapshot(
        completedOrders: 18,
        hubIncome: 144000,
        hubBonus: 18000,
        customerTip: 12000,
        shopeepayBalance: 350000,
        gopayBalance: 50000,
        gopayMerchantBalance: 25000,
      );

      expect(mockDio.capturedPath, '/driver/snapshots');
      expect(mockDio.capturedData, {
        'completed_orders': 18,
        'hub_income': 144000,
        'hub_bonus': 18000,
        'customer_tip': 12000,
        'shopeepay_balance': 350000,
        'gopay_balance': 50000,
        'gopay_merchant_balance': 25000,
      });
    });
  });
}
