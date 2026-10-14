class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final dynamic data;

  const ApiException({
    required this.message,
    this.statusCode,
    this.data,
  });

  @override
  String toString() => 'ApiException(statusCode: $statusCode, message: $message)';
}

class NetworkException extends ApiException {
  const NetworkException({String message = 'Koneksi internet bermasalah atau server tidak terjangkau.'})
      : super(message: message, statusCode: null);
}

class ValidationException extends ApiException {
  final Map<String, dynamic> errors;

  const ValidationException({
    required String message,
    required this.errors,
    int? statusCode = 422,
  }) : super(message: message, statusCode: statusCode, data: errors);
}
