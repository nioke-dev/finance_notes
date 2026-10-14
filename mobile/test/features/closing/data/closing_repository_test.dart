import 'package:flutter_test/flutter_test.dart';
import 'package:dio/dio.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';
import 'package:driver_finance_mobile/features/closing/data/closing_repository.dart';
import 'package:driver_finance_mobile/features/closing/data/models/reconciliation_result_model.dart';

class MockDioClosing extends Fake implements Dio {
  String? capturedPath;
  dynamic capturedData;

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

    final fakeResponse = {
      'status': 'success',
      'data': [
        {'id': 1, 'work_date': '2026-10-13'},
        {'id': 2, 'work_date': '2026-10-14'},
      ]
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

    final fakeResponse = {
      'status': 'success',
      'data': {
        'is_balanced': true,
        'difference': 0,
        'physical_total': 200000,
        'book_balance': 200000,
        'status': 'matched',
      }
    };

    return Response<T>(
      requestOptions: RequestOptions(path: path),
      data: fakeResponse as T,
      statusCode: 200,
    );
  }
}

void main() {
  group('ClosingRepository Tests', () {
    late MockDioClosing mockDio;
    late ApiClient apiClient;
    late ClosingRepository repository;

    setUp(() {
      mockDio = MockDioClosing();
      apiClient = ApiClient(customDio: mockDio);
      repository = ClosingRepository(apiClient: apiClient);
    });

    test('getPendingReconciliationsCount returns correct count', () async {
      final count = await repository.getPendingReconciliationsCount();

      expect(mockDio.capturedPath, '/reconciliation/unfinalized-dates');
      expect(count, 2);
    });

    test('calculateDifference formats denomination array correctly and returns model', () async {
      final result = await repository.calculateDifference(
        1,
        {100000: 2},
      );

      expect(mockDio.capturedPath, '/reconciliation/calculate-difference');
      expect(mockDio.capturedData, {
        'work_day_id': 1,
        'denominations': [
          {'nominal': 100000, 'quantity': 2},
        ],
      });
      expect(result.isBalanced, true);
      expect(result.difference, 0);
      expect(result.physicalTotal, 200000);
    });
  });
}
