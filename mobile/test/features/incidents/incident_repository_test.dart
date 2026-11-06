import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/features/incidents/repositories/incident_repository.dart';
import 'package:http/http.dart' as http;

void main() {
  test('lists incidents with filters and pagination', () async {
    final client = _FakeHttpClient((request) async {
      expect(request.method, 'GET');
      expect(request.url.path, '/api/v1/incidencias');
      expect(request.url.queryParameters, {
        'page': '2',
        'per_page': '10',
        'search': 'mesa',
        'prioridad': 'ALTA',
        'estado': 'ABIERTA',
      });

      return _jsonResponse({
        'data': [_incidentJson()],
        'links': {},
        'meta': {'current_page': 2, 'last_page': 3, 'total': 21},
        'message': 'Incidencias consultadas correctamente.',
      });
    });

    final page = await _createRepository(client).getIncidents(
      page: 2,
      search: ' mesa ',
      priority: 'ALTA',
      status: 'ABIERTA',
    );

    expect(page.incidents.single.reporterName, 'Ana Pérez');
    expect(page.lastPage, 3);
    expect(page.total, 21);
  });

  test('reports without sending server-controlled fields', () async {
    final client = _FakeHttpClient((request) async {
      expect(request.method, 'POST');
      expect(request.url.path, '/api/v1/incidencias');
      expect(jsonDecode((request as http.Request).body), {
        'titulo': 'Mesa dañada',
        'descripcion': 'Una pata está suelta.',
        'prioridad': 'ALTA',
      });

      return _jsonResponse({'data': _incidentJson()}, statusCode: 201);
    });

    final incident = await _createRepository(client).reportIncident(
      title: ' Mesa dañada ',
      description: ' Una pata está suelta. ',
      priority: 'ALTA',
    );

    expect(incident.id, 73);
    expect(incident.status, 'ABIERTA');
  });

  test('updates incident priority and status through the API', () async {
    final client = _FakeHttpClient((request) async {
      expect(request.method, 'PATCH');
      expect(request.url.path, '/api/v1/incidencias/73');
      expect(jsonDecode((request as http.Request).body), {
        'prioridad': 'CRITICA',
        'estado': 'EN_ATENCION',
      });

      return _jsonResponse({
        'data': {
          ..._incidentJson(),
          'prioridad': 'CRITICA',
          'estado': 'EN_ATENCION',
        },
      });
    });

    final incident = await _createRepository(client).updateIncident(
      incidentId: 73,
      priority: 'CRITICA',
      status: 'EN_ATENCION',
    );

    expect(incident.priority, 'CRITICA');
    expect(incident.status, 'EN_ATENCION');
  });
}

Map<String, dynamic> _incidentJson() => {
  'id': 73,
  'usuario_reporta_id': 2,
  'mesa_id': null,
  'pedido_id': null,
  'titulo': 'Mesa dañada',
  'descripcion': 'Una pata está suelta.',
  'prioridad': 'ALTA',
  'estado': 'ABIERTA',
  'usuario': {'id': 2, 'nombres': 'Ana', 'apellidos': 'Pérez'},
};

ApiIncidentRepository _createRepository(http.Client client) {
  return ApiIncidentRepository(
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
  Future<http.StreamedResponse> send(http.BaseRequest request) {
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
