import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  const ApiException({
    required this.message,
    this.statusCode,
    this.validationErrors = const {},
    this.retryAfter,
  });

  factory ApiException.fromResponse(http.Response response) {
    Map<String, dynamic> payload = const {};
    try {
      final decoded = jsonDecode(utf8.decode(response.bodyBytes));
      if (decoded is Map<String, dynamic>) {
        payload = decoded;
      }
    } on FormatException {
      payload = const {};
    }

    final statusCode = response.statusCode;
    final bodyMessage = payload['message'];
    final safeMessage = statusCode >= 500
        ? _fallbackMessage(statusCode)
        : bodyMessage is String && bodyMessage.isNotEmpty
        ? bodyMessage
        : _fallbackMessage(statusCode);
    final retryAfterSeconds = int.tryParse(
      response.headers['retry-after'] ?? '',
    );

    return ApiException(
      statusCode: statusCode,
      message: safeMessage,
      validationErrors: _validationErrors(payload['meta']),
      retryAfter: retryAfterSeconds == null
          ? null
          : Duration(seconds: retryAfterSeconds),
    );
  }

  final int? statusCode;
  final String message;
  final Map<String, List<String>> validationErrors;
  final Duration? retryAfter;

  static Map<String, List<String>> _validationErrors(Object? meta) {
    if (meta is! Map<String, dynamic> || meta['errors'] is! Map) {
      return const {};
    }

    final errors = <String, List<String>>{};
    (meta['errors'] as Map).forEach((key, value) {
      if (key is String && value is List) {
        errors[key] = value.whereType<String>().toList(growable: false);
      }
    });

    return Map.unmodifiable(errors);
  }

  static String _fallbackMessage(int statusCode) {
    return switch (statusCode) {
      401 => 'La sesión no está autenticada o ha expirado.',
      403 => 'No tienes permiso para realizar esta acción.',
      404 => 'No se encontró el recurso solicitado.',
      409 => 'La solicitud entra en conflicto con el estado actual.',
      422 => 'Revisa los datos ingresados.',
      429 => 'Hay demasiadas solicitudes. Inténtalo más tarde.',
      >= 500 => 'Ocurrió un error interno. Inténtalo más tarde.',
      _ => 'No fue posible completar la solicitud.',
    };
  }

  @override
  String toString() => message;
}
