import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product_page.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiCatalogRepository', () {
    test(
      'requests products with pagination and an optional search term',
      () async {
        final client = _FakeHttpClient(
          (request) async => _jsonResponse({
            'data': [_productJson()],
            'links': {'next': null, 'prev': null},
            'meta': {
              'current_page': 2,
              'last_page': 3,
              'per_page': 12,
              'total': 25,
            },
            'message': 'Productos consultados correctamente.',
          }),
        );
        final repository = _createRepository(client);

        final page = await repository.getProducts(
          page: 2,
          perPage: 12,
          search: '  sopa ',
        );

        expect(client.lastRequest?.url.path, '/api/v1/productos');
        expect(client.lastRequest?.url.queryParameters, {
          'page': '2',
          'per_page': '12',
          'search': 'sopa',
        });
        expect(page.products.single.name, 'Sopa');
        expect(page.products.single.price, 12500);
        expect(page.currentPage, 2);
        expect(page.lastPage, 3);
        expect(page.total, 25);
        expect(page.hasPrevious, isTrue);
        expect(page.hasNext, isTrue);
      },
    );

    test('maps the current published menu and its products', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': {
            'id': 8,
            'fecha': '2026-11-05',
            'estado': 'PUBLICADO',
            'productos': [_productJson()],
          },
          'message': 'Menú del día consultado correctamente.',
          'meta': {},
        }),
      );
      final repository = _createRepository(client);

      final menu = await repository.getDailyMenu();

      expect(client.lastRequest?.url.path, '/api/v1/menus-dia/actual');
      expect(menu.date, DateTime(2026, 11, 5));
      expect(menu.status, 'PUBLICADO');
      expect(menu.products.single.name, 'Sopa');
    });

    test('rejects invalid pagination data instead of creating fake values', () {
      expect(
        () => ProductPage.fromJson({'data': [], 'meta': {}}),
        throwsA(isA<ApiException>()),
      );
    });
  });
}

Map<String, dynamic> _productJson() => {
  'id': 5,
  'nombre': 'Sopa',
  'descripcion': 'Sopa del día',
  'precio': '12500.00',
  'disponible': true,
};

ApiCatalogRepository _createRepository(http.Client client) {
  return ApiCatalogRepository(
    ApiClient(
      config: const ApiConfig(baseUrl: 'https://api.example.test'),
      httpClient: client,
    ),
  );
}

class _FakeHttpClient extends http.BaseClient {
  _FakeHttpClient(this.handler);

  final Future<http.StreamedResponse> Function(http.BaseRequest request)
  handler;

  http.BaseRequest? lastRequest;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    lastRequest = request;

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
