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

    test('lists menus for staff with filters and pagination', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': [_menuJson()],
          'links': {},
          'meta': {
            'current_page': 2,
            'last_page': 3,
            'per_page': 10,
            'total': 21,
          },
          'message': 'Menús del día consultados correctamente.',
        }),
      );
      final page = await _createRepository(
        client,
      ).getMenus(page: 2, perPage: 10, status: 'BORRADOR');

      expect(client.lastRequest?.url.path, '/api/v1/menus-dia');
      expect(client.lastRequest?.url.queryParameters, {
        'page': '2',
        'per_page': '10',
        'estado': 'BORRADOR',
      });
      expect(page.menus.single.id, 8);
      expect(page.currentPage, 2);
      expect(page.total, 21);
    });

    test('loads available menu products through the catalog API', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': [_productJson()],
          'links': {},
          'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
          'message': 'Productos consultados correctamente.',
        }),
      );

      final products = await _createRepository(
        client,
      ).getMenuProducts(search: ' sopa ');

      expect(client.lastRequest?.url.path, '/api/v1/productos');
      expect(client.lastRequest?.url.queryParameters, {
        'page': '1',
        'per_page': '100',
        'search': 'sopa',
      });
      expect(products.single.name, 'Sopa');
    });

    test('creates a menu draft without assigning its state', () async {
      final client = _FakeHttpClient((request) async {
        expect(request.method, 'POST');
        expect(request.url.path, '/api/v1/menus-dia');
        expect(jsonDecode((request as http.Request).body), {
          'fecha': '2026-11-08',
          'productos': [5],
        });

        return _jsonResponse({'data': _menuJson()}, statusCode: 201);
      });

      final menu = await _createRepository(
        client,
      ).createMenu(date: DateTime(2026, 11, 8), productIds: [5]);

      expect(menu.status, 'BORRADOR');
    });

    test(
      'updates drafts and publishes through their dedicated endpoints',
      () async {
        final client = _FakeHttpClient((request) async {
          if (request.method == 'PATCH') {
            expect(request.url.path, '/api/v1/menus-dia/8');
            expect(jsonDecode((request as http.Request).body), {
              'fecha': '2026-11-09',
              'productos': [5],
            });
          } else {
            expect(request.method, 'POST');
            expect(request.url.path, '/api/v1/menus-dia/8/publicacion');
          }

          return _jsonResponse({
            'data': {
              ..._menuJson(),
              if (request.url.path.endsWith('/publicacion'))
                'estado': 'PUBLICADO',
            },
          });
        });
        final repository = _createRepository(client);

        final updated = await repository.updateMenu(
          menuId: 8,
          date: DateTime(2026, 11, 9),
          productIds: [5],
        );
        final published = await repository.publishMenu(8);

        expect(updated.status, 'BORRADOR');
        expect(published.status, 'PUBLICADO');
      },
    );

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

Map<String, dynamic> _menuJson() => {
  'id': 8,
  'fecha': '2026-11-08',
  'estado': 'BORRADOR',
  'productos': [_productJson()],
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
