import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'api_config.dart';
import 'api_exception.dart';

typedef AccessTokenProvider = Future<String?> Function();
typedef UnauthorizedHandler = Future<void> Function();

class ApiClient {
  ApiClient({
    required this._config,
    required this._httpClient,
    this._accessTokenProvider,
    this._unauthorizedHandler,
  });

  final ApiConfig _config;
  final http.Client _httpClient;
  final AccessTokenProvider? _accessTokenProvider;
  final UnauthorizedHandler? _unauthorizedHandler;

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, String>? queryParameters,
  }) {
    return _send('GET', path, queryParameters: queryParameters);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Object? body,
    Map<String, String>? headers,
  }) {
    return _send('POST', path, body: body, headers: headers);
  }

  Future<Map<String, dynamic>> patch(
    String path, {
    Object? body,
    Map<String, String>? headers,
  }) {
    return _send('PATCH', path, body: body, headers: headers);
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Object? body,
    Map<String, String>? queryParameters,
    Map<String, String>? headers,
  }) async {
    final requestHeaders = <String, String>{
      'Accept': 'application/json',
      if (body != null) 'Content-Type': 'application/json; charset=UTF-8',
      ...?headers,
    };
    final accessToken = (await _accessTokenProvider?.call())?.trim();
    if (accessToken != null && accessToken.isNotEmpty) {
      requestHeaders['Authorization'] = 'Bearer $accessToken';
    }

    final request = http.Request(
      method,
      _config.uriFor(path, queryParameters: queryParameters),
    )..headers.addAll(requestHeaders);
    if (body != null) {
      request.body = jsonEncode(body);
    }

    try {
      final streamedResponse = await _httpClient
          .send(request)
          .timeout(_config.requestTimeout);
      final response = await http.Response.fromStream(
        streamedResponse,
      ).timeout(_config.requestTimeout);

      if (response.statusCode == 401 &&
          accessToken != null &&
          accessToken.isNotEmpty) {
        await _unauthorizedHandler?.call();
      }

      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw ApiException.fromResponse(response);
      }

      final decoded = jsonDecode(utf8.decode(response.bodyBytes));
      if (decoded is! Map<String, dynamic>) {
        throw const ApiException(
          message: 'El servidor devolvió una respuesta no válida.',
        );
      }

      return decoded;
    } on ApiException {
      rethrow;
    } on TimeoutException {
      throw const ApiException(
        message: 'La solicitud tardó demasiado. Inténtalo de nuevo.',
      );
    } on SocketException {
      throw const ApiException(
        message: 'No se pudo conectar con el servidor. Verifica tu conexión.',
      );
    } on http.ClientException {
      throw const ApiException(
        message: 'No se pudo conectar con el servidor. Verifica tu conexión.',
      );
    } on FormatException {
      throw const ApiException(
        message: 'El servidor devolvió una respuesta no válida.',
      );
    }
  }
}
