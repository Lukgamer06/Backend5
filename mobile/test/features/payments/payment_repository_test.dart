import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/payments/repositories/payment_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiPaymentRepository', () {
    test('lists payments through the order-scoped endpoint', () async {
      final client = _FakeHttpClient((request) async {
        expect(request.method, 'GET');
        expect(request.url.path, '/api/v1/pedidos/123/pagos');

        return _jsonResponse({
          'data': [_paymentJson()],
          'message': 'Pagos consultados.',
          'meta': {'count': 1},
        });
      });

      final payments = await _createRepository(client).getForOrder(123);

      expect(payments.single.id, 80);
      expect(payments.single.amount, 12500);
      expect(payments.single.status, 'PENDIENTE');
    });

    test(
      'requests online payments with idempotency key and empty body',
      () async {
        final client = _FakeHttpClient((request) async {
          expect(request.method, 'POST');
          expect(request.url.path, '/api/v1/pedidos/123/pagos/enlinea');
          expect(request.headers['idempotency-key'], 'payment-key-1');
          expect((request as http.Request).body, isEmpty);

          return _jsonResponse({'data': _paymentJson()}, statusCode: 201);
        });

        final payment = await _createRepository(
          client,
        ).requestOnlinePayment(123, 'payment-key-1');

        expect(payment.status, 'PENDIENTE');
        expect(payment.amount, 12500);
      },
    );

    test('accepts idempotent 200 response for in-person payments', () async {
      final client = _FakeHttpClient((request) async {
        expect(request.method, 'POST');
        expect(request.url.path, '/api/v1/pedidos/123/pagos/presenciales');
        expect(request.headers['idempotency-key'], 'payment-key-2');

        return _jsonResponse({'data': _paymentJson()}, statusCode: 200);
      });

      final payment = await _createRepository(
        client,
      ).registerInPersonPayment(123, 'payment-key-2');

      expect(payment.id, 80);
    });

    for (final statusCode in [409, 422]) {
      test('preserves API errors for HTTP $statusCode', () async {
        final client = _FakeHttpClient((request) async {
          return _jsonResponse({
            'message': 'La solicitud de pago no es válida.',
          }, statusCode: statusCode);
        });

        await expectLater(
          _createRepository(
            client,
          ).requestOnlinePayment(123, 'payment-key-error'),
          throwsA(
            isA<ApiException>().having(
              (error) => error.statusCode,
              'statusCode',
              statusCode,
            ),
          ),
        );
      });
    }
  });
}

Map<String, dynamic> _paymentJson() => {
  'id': 80,
  'pedido_id': 123,
  'monto': '12500.00',
  'canal': 'EN_LINEA',
  'estado': 'PENDIENTE',
  'referencia_operacion': 'reference-80',
};

ApiPaymentRepository _createRepository(http.Client client) {
  return ApiPaymentRepository(
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

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) =>
      handler(request);
}

http.StreamedResponse _jsonResponse(Object body, {int statusCode = 200}) {
  return http.StreamedResponse(
    Stream.value(utf8.encode(jsonEncode(body))),
    statusCode,
    headers: {'content-type': 'application/json; charset=utf-8'},
  );
}
