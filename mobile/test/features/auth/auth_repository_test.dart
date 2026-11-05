import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/core/storage/token_storage.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiAuthRepository', () {
    test('logs in, stores the token, and returns the user DTO', () async {
      final tokenStorage = _FakeTokenStorage();
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': {
            'access_token': 'token-value',
            'token_type': 'Bearer',
            'usuario': _userJson(),
          },
          'message': 'Autenticación exitosa.',
          'meta': {},
        }),
      );
      final repository = _createRepository(client, tokenStorage);

      final user = await repository.login('ana@example.test', 'secret');

      expect(user.nombreCompleto, 'Ana Pérez');
      expect(user.rol, 'CLIENTE');
      expect(tokenStorage.token, 'token-value');
      expect(client.lastRequest?.method, 'POST');
      expect(client.lastRequest?.url.path, '/api/v1/login');
      expect(jsonDecode(client.lastRequestBody!), {
        'correo': 'ana@example.test',
        'password': 'secret',
      });
    });

    test('restores the user from /me using the stored Sanctum token', () async {
      final tokenStorage = _FakeTokenStorage()..token = 'token-value';
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': {'usuario': _userJson()},
          'message': 'Usuario autenticado.',
          'meta': {},
        }),
      );
      final repository = _createRepository(client, tokenStorage);

      final user = await repository.restoreSession();

      expect(user?.correo, 'ana@example.test');
      expect(client.lastRequest?.method, 'GET');
      expect(client.lastRequest?.url.path, '/api/v1/me');
      expect(
        client.lastRequest?.headers['authorization'],
        'Bearer token-value',
      );
    });

    test('does not call /me when no token is stored', () async {
      final tokenStorage = _FakeTokenStorage();
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({'data': {}, 'meta': {}}),
      );
      final repository = _createRepository(client, tokenStorage);

      expect(await repository.restoreSession(), isNull);
      expect(client.lastRequest, isNull);
    });

    test('deletes the local token when logout cannot reach the API', () async {
      final tokenStorage = _FakeTokenStorage()..token = 'token-value';
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': null,
          'message': 'No autenticado.',
          'meta': {},
        }, statusCode: 401),
      );
      final repository = _createRepository(client, tokenStorage);

      await expectLater(repository.logout(), throwsA(isA<ApiException>()));

      expect(tokenStorage.token, isNull);
    });
  });
}

ApiAuthRepository _createRepository(
  http.Client client,
  TokenStorage tokenStorage,
) {
  return ApiAuthRepository(
    apiClient: ApiClient(
      config: const ApiConfig(baseUrl: 'https://api.example.test'),
      httpClient: client,
      accessTokenProvider: tokenStorage.readToken,
    ),
    tokenStorage: tokenStorage,
  );
}

Map<String, dynamic> _userJson() => {
  'id': 42,
  'nombres': 'Ana',
  'apellidos': 'Pérez',
  'correo': 'ana@example.test',
  'telefono': null,
  'rol': 'CLIENTE',
};

class _FakeTokenStorage implements TokenStorage {
  String? token;

  @override
  Future<String?> readToken() async => token;

  @override
  Future<void> writeToken(String token) async {
    this.token = token;
  }

  @override
  Future<void> deleteToken() async {
    token = null;
  }
}

class _FakeHttpClient extends http.BaseClient {
  _FakeHttpClient(this.handler);

  final Future<http.StreamedResponse> Function(http.BaseRequest request)
  handler;

  http.BaseRequest? lastRequest;
  String? lastRequestBody;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    lastRequest = request;
    if (request is http.Request) {
      lastRequestBody = request.body;
    }

    return handler(request);
  }
}

http.StreamedResponse _jsonResponse(Object body, {int statusCode = 200}) {
  return http.StreamedResponse(
    Stream.value(utf8.encode(jsonEncode(body))),
    statusCode,
    headers: {'content-type': 'application/json; charset=utf-8'},
  );
}
