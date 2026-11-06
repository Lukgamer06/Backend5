import 'package:flutter/foundation.dart';

import 'api_client.dart';

abstract class BaseRepository {
  const BaseRepository(this.apiClient);

  final ApiClient apiClient;

  @protected
  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, String>? queryParameters,
  }) {
    return apiClient.get(path, queryParameters: queryParameters);
  }

  @protected
  Future<Map<String, dynamic>> post(
    String path, {
    Object? body,
    Map<String, String>? headers,
  }) {
    return apiClient.post(path, body: body, headers: headers);
  }

  @protected
  Future<Map<String, dynamic>> patch(
    String path, {
    Object? body,
    Map<String, String>? headers,
  }) {
    return apiClient.patch(path, body: body, headers: headers);
  }
}
