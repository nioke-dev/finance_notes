import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';
import 'package:driver_finance_mobile/core/network/api_exceptions.dart';

void main() {
  group('ApiClient Tests', () {
    test('ApiClient sets correct base headers and timeout options', () {
      final client = ApiClient();

      expect(client.dio.options.headers['Accept'], 'application/json');
      expect(client.dio.options.headers['Content-Type'], 'application/json');
      expect(client.dio.options.baseUrl, 'http://10.0.2.2:8000/api/v1');
      expect(client.dio.options.connectTimeout, const Duration(seconds: 10));
      expect(client.dio.options.receiveTimeout, const Duration(seconds: 10));
    });

    test('ApiClient sets Authorization header when token is supplied', () {
      final client = ApiClient(token: 'secret_driver_token_123');

      expect(client.dio.options.headers['Authorization'], 'Bearer secret_driver_token_123');
    });

    test('ApiException formats message and status code correctly', () {
      const exception = ApiException(message: 'Saldo tidak mencukupi', statusCode: 400);

      expect(exception.message, 'Saldo tidak mencukupi');
      expect(exception.statusCode, 400);
      expect(exception.toString(), 'ApiException(statusCode: 400, message: Saldo tidak mencukupi)');
    });

    test('ValidationException carries field errors', () {
      const valException = ValidationException(
        message: 'Validasi form gagal',
        errors: {
          'received_amount': ['Jumlah harus lebih besar dari Rp0']
        },
      );

      expect(valException.statusCode, 422);
      expect(valException.errors['received_amount'], isNotEmpty);
    });
  });
}
