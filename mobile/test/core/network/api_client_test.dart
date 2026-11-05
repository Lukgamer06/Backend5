import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiConfig', () {
    test('builds versioned URLs and encodes query parameters', () {
      const config = ApiConfig(baseUrl: 'https://api.example.test');

      final uri = config.uriFor(
        'reservas',
        queryParameters: {'search': 'mesa 1', 'per_page': '15'},
      );

      expect(uri.toString(), contains('/api/v1/reservas?'));
      expect(uri.queryParameters, {'search': 'mesa 1', 'per_page': '15'});
    });

    test('rejects a base URL containing credentials or a path', () {
      const config = ApiConfig(baseUrl: 'https://user:secret@example.test/api');

      expect(() => config.uriFor('me'), throwsArgumentError);
    });
  });

  group('ApiClient', () {
    test(
      'sends JSON headers, bearer token, and encoded request body',
      () async {
        final client = _FakeHttpClient(
          (request) async => _jsonResponse({
            'data': {'id': 42},
            'message': 'OK',
            'meta': {},
          }),
        );
        final apiClient = ApiClient(
          config: const ApiConfig(baseUrl: 'https://api.example.test'),
          httpClient: client,
          accessTokenProvider: () async => 'token-123',
        );

        final response = await apiClient.post(
          'pedidos',
          body: {
            'lineas': [
              {'producto_id': 4, 'cantidad': 2},
            ],
          },
        );

        expect(response['message'], 'OK');
        expect(client.lastRequest?.url.path, '/api/v1/pedidos');
        expect(client.lastRequest?.headers['accept'], 'application/json');
        expect(client.lastRequest?.headers['content-type'], contains('UTF-8'));
        expect(
          client.lastRequest?.headers['authorization'],
          'Bearer token-123',
        );
        expect(jsonDecode(client.lastRequestBody!), {
          'lineas': [
            {'producto_id': 4, 'cantidad': 2},
          ],
        });
      },
    );

    test('omits bearer authorization when no token exists', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({'data': [], 'message': 'OK'}),
      );
      final apiClient = ApiClient(
        config: const ApiConfig(baseUrl: 'http://10.0.2.2:8080'),
        httpClient: client,
      );

      await apiClient.get('productos', queryParameters: {'per_page': '10'});

      expect(client.lastRequest?.headers.containsKey('authorization'), isFalse);
      expect(client.lastRequest?.url.queryParameters['per_page'], '10');
    });

    test('converts validation responses into a safe ApiException', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': null,
          'message': 'Los datos enviados no son válidos.',
          'meta': {
            'errors': {
              'fecha_inicio': ['La fecha no es válida.'],
            },
          },
        }, statusCode: 422),
      );
      final apiClient = ApiClient(
        config: const ApiConfig(baseUrl: 'https://api.example.test'),
        httpClient: client,
      );

      await expectLater(
        apiClient.post('reservas', body: {}),
        throwsA(
          isA<ApiException>()
              .having((error) => error.statusCode, 'statusCode', 422)
              .having(
                (error) => error.message,
                'message',
                'Los datos enviados no son válidos.',
              )
              .having(
                (error) => error.validationErrors['fecha_inicio'],
                'validationErrors',
                ['La fecha no es válida.'],
              ),
        ),
      );
    });

    test('does not expose server error details', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'message': 'Database password is secret',
        }, statusCode: 500),
      );
      final apiClient = ApiClient(
        config: const ApiConfig(baseUrl: 'https://api.example.test'),
        httpClient: client,
      );

      await expectLater(
        apiClient.get('me'),
        throwsA(
          isA<ApiException>()
              .having((error) => error.statusCode, 'statusCode', 500)
              .having(
                (error) => error.message,
                'message',
                'Ocurrió un error interno. Inténtalo más tarde.',
              ),
        ),
      );
    });
  });
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
