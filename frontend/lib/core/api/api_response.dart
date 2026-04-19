/// Generic API Response Wrapper
///
/// Provides a consistent way to handle API responses across the app.
/// Supports both success and error states with proper typing.

class ApiResponse<T> {
  final T? data;
  final String? error;
  final bool isSuccess;
  final int? statusCode;
  final Map<String, dynamic>? metadata;

  ApiResponse._({
    this.data,
    this.error,
    required this.isSuccess,
    this.statusCode,
    this.metadata,
  });

  /// Create a successful response
  factory ApiResponse.success(
    T data, {
    int? statusCode,
    Map<String, dynamic>? metadata,
  }) {
    return ApiResponse._(
      data: data,
      error: null,
      isSuccess: true,
      statusCode: statusCode ?? 200,
      metadata: metadata,
    );
  }

  /// Create an error response
  factory ApiResponse.error(
    String error, {
    int? statusCode,
    Map<String, dynamic>? metadata,
  }) {
    return ApiResponse._(
      data: null,
      error: error,
      isSuccess: false,
      statusCode: statusCode,
      metadata: metadata,
    );
  }

  bool get isLoading => metadata?['loading'] == true;

  T get dataOrThrow {
    if (isSuccess && data != null) return data!;
    throw Exception(error ?? 'Unknown error');
  }

  T dataOr(T defaultValue) => isSuccess && data != null ? data! : defaultValue;

  @override
  String toString() {
    return isSuccess
        ? 'ApiResponse.success(data: $data, statusCode: $statusCode)'
        : 'ApiResponse.error(error: $error, statusCode: $statusCode)';
  }
}
