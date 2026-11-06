import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/features/orders/repositories/order_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiOrderRepository', () {
    test('lists orders with pagination and status filters', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': [_orderJson()],
          'links': {'next': null, 'prev': null},
          'meta': {
            'current_page': 2,
            'last_page': 4,
            'per_page': 10,
            'total': 35,
          },
          'message': 'Pedidos consultados correctamente.',
        }),
      );
      final repository = _createRepository(client);

      final result = await repository.getOrders(page: 2, status: 'REGISTRADO');

      expect(client.lastRequest?.url.path, '/api/v1/pedidos');
      expect(client.lastRequest?.url.queryParameters, {
        'page': '2',
        'per_page': '10',
        'estado': 'REGISTRADO',
      });
      expect(result.currentPage, 2);
      expect(result.lastPage, 4);
      expect(result.orders.single.lines.single.name, 'Sopa');
      expect(result.orders.single.customerName, 'Ana Pérez');
    });

    test('creates an order without trusting a client-supplied total', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({'data': _orderJson()}),
      );
      final repository = _createRepository(client);

      final order = await repository.createOrder(
        attentionType: 'PROGRAMADO',
        serviceMode: 'SIN_MESA',
        scheduledAt: DateTime(2026, 11, 6, 14, 30),
        lines: const [
          {'producto_id': 9, 'cantidad': 2},
        ],
      );

      expect(client.lastRequest?.method, 'POST');
      expect(client.lastRequest?.url.path, '/api/v1/pedidos');
      expect(jsonDecode(client.lastRequestBody!), {
        'tipo_atencion': 'PROGRAMADO',
        'modalidad_servicio': 'SIN_MESA',
        'fecha_programada': '2026-11-06T14:30',
        'lineas': [
          {'producto_id': 9, 'cantidad': 2},
        ],
      });
      expect(order.total, 25000);
      expect(order.id, 123);
    });

    test('requests cancellation using the action endpoint', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': {..._orderJson(), 'estado': 'CANCELADO'},
        }),
      );
      final repository = _createRepository(client);

      final order = await repository.cancelOrder(123);

      expect(client.lastRequest?.method, 'POST');
      expect(client.lastRequest?.url.path, '/api/v1/pedidos/123/cancelacion');
      expect(order.status, 'CANCELADO');
    });

    test(
      'changes order status through the protected transition endpoint',
      () async {
        final client = _FakeHttpClient((request) async {
          expect(request.method, 'PATCH');
          expect(request.url.path, '/api/v1/pedidos/123/estado');
          expect(jsonDecode((request as http.Request).body), {
            'estado': 'LISTO',
          });

          return _jsonResponse({
            'data': {..._orderJson(), 'estado': 'LISTO'},
          });
        });
        final repository = _createRepository(client);

        final order = await repository.changeOrderStatus(123, 'LISTO');

        expect(order.status, 'LISTO');
      },
    );
  });
}

Map<String, dynamic> _orderJson() => {
  'id': 123,
  'usuario_id': 7,
  'cliente_id': 8,
  'reserva_id': null,
  'mesa_id': null,
  'tipo_atencion': 'PROGRAMADO',
  'modalidad_servicio': 'SIN_MESA',
  'estado': 'REGISTRADO',
  'fecha_programada': '2026-11-06T14:30:00-05:00',
  'total': '25000.00',
  'cliente': {'nombres': 'Ana', 'apellidos': 'Pérez'},
  'detalles': [
    {
      'id': 1,
      'producto_id': 9,
      'cantidad': 2,
      'precio_unitario': '12500.00',
      'subtotal': '25000.00',
      'observaciones': null,
      'producto': {
        'id': 9,
        'nombre': 'Sopa',
        'descripcion': null,
        'precio': '12500.00',
        'disponible': true,
      },
    },
  ],
  'pagos': [],
};

ApiOrderRepository _createRepository(http.Client client) {
  return ApiOrderRepository(
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
