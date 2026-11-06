import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/features/reservations/repositories/reservation_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  group('ApiReservationRepository', () {
    test('queries available tables with validated criteria', () async {
      final client = _FakeHttpClient((request) async {
        expect(request.method, 'GET');
        expect(request.url.path, '/api/v1/reservas/disponibilidad');
        expect(request.url.queryParameters, {
          'fecha_inicio': '2026-12-10T18:00',
          'fecha_fin': '2026-12-10T19:30',
          'numero_personas': '3',
        });

        return _jsonResponse({
          'data': [
            {'id': 8, 'numero': 4, 'capacidad': 4},
          ],
          'links': {},
          'meta': {},
          'message': 'Mesas disponibles consultadas correctamente.',
        });
      });

      final tables = await _createRepository(client).getAvailableTables(
        startsAt: DateTime(2026, 12, 10, 18),
        endsAt: DateTime(2026, 12, 10, 19, 30),
        guestCount: 3,
      );

      expect(tables.single.id, 8);
      expect(tables.single.number, 4);
      expect(tables.single.capacity, 4);
    });

    test('lists reservations with status filter and page metadata', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': [_reservationJson()],
          'links': {'next': null, 'prev': null},
          'meta': {
            'current_page': 2,
            'last_page': 3,
            'per_page': 10,
            'total': 21,
          },
          'message': 'Reservas consultadas correctamente.',
        }),
      );

      final page = await _createRepository(
        client,
      ).getReservations(page: 2, status: 'CONFIRMADA');

      expect(client.lastRequest?.url.path, '/api/v1/reservas');
      expect(client.lastRequest?.url.queryParameters, {
        'page': '2',
        'per_page': '10',
        'estado': 'CONFIRMADA',
      });
      expect(page.currentPage, 2);
      expect(page.reservations.single.tableNumber, 4);
      expect(page.reservations.single.canCancel, isTrue);
    });

    test(
      'creates a reservation without sending server-controlled fields',
      () async {
        final client = _FakeHttpClient((request) async {
          expect(request.method, 'POST');
          expect(request.url.path, '/api/v1/reservas');
          expect(jsonDecode((request as http.Request).body), {
            'mesa_id': 8,
            'fecha_inicio': '2026-12-10T18:00',
            'fecha_fin': '2026-12-10T19:00',
            'numero_personas': 3,
            'observaciones': 'Celebración',
          });

          return _jsonResponse({'data': _reservationJson()}, statusCode: 201);
        });

        final reservation = await _createRepository(client).createReservation(
          tableId: 8,
          startsAt: DateTime(2026, 12, 10, 18),
          endsAt: DateTime(2026, 12, 10, 19),
          guestCount: 3,
          notes: 'Celebración',
        );

        expect(reservation.id, 90);
        expect(reservation.status, 'CONFIRMADA');
      },
    );

    test('cancels a reservation through its action endpoint', () async {
      final client = _FakeHttpClient(
        (request) async => _jsonResponse({
          'data': {..._reservationJson(), 'estado': 'CANCELADA'},
        }),
      );

      final reservation = await _createRepository(client).cancelReservation(90);

      expect(client.lastRequest?.method, 'POST');
      expect(client.lastRequest?.url.path, '/api/v1/reservas/90/cancelacion');
      expect(reservation.status, 'CANCELADA');
      expect(reservation.canCancel, isFalse);
    });
  });
}

Map<String, dynamic> _reservationJson() => {
  'id': 90,
  'usuario_id': 1,
  'cliente_id': 2,
  'mesa_id': 8,
  'fecha_inicio': '2026-12-10T18:00:00-05:00',
  'fecha_fin': '2026-12-10T19:00:00-05:00',
  'numero_personas': 3,
  'estado': 'CONFIRMADA',
  'observaciones': 'Celebración',
  'mesa': {'id': 8, 'numero': 4, 'capacidad': 4},
};

ApiReservationRepository _createRepository(http.Client client) {
  return ApiReservationRepository(
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
