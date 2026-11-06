import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/incidents/models/incident.dart';
import 'package:hostalconnect_mobile/features/incidents/providers/incident_controller.dart';
import 'package:hostalconnect_mobile/features/incidents/repositories/incident_repository.dart';

void main() {
  test('reports and reloads incidents', () async {
    final repository = _FakeIncidentRepository();
    final controller = IncidentController(repository);

    final incident = await controller.reportIncident(
      title: 'Mesa dañada',
      description: 'Una pata está suelta.',
      priority: 'ALTA',
    );

    expect(incident?.id, 73);
    expect(repository.reportCalls, 1);
    expect(repository.loadCalls, 1);
    expect(controller.incidents.single.status, 'ABIERTA');

    controller.dispose();
  });

  test('keeps API authorization and validation failures visible', () async {
    final controller = IncidentController(
      _FakeIncidentRepository(
        reportError: const ApiException(
          message: 'No tienes permiso para reportar incidencias.',
          statusCode: 403,
        ),
      ),
    );

    final incident = await controller.reportIncident(
      title: 'Reporte',
      description: 'Descripción',
      priority: 'MEDIA',
    );

    expect(incident, isNull);
    expect(
      controller.actionError,
      'No tienes permiso para reportar incidencias.',
    );

    controller.dispose();
  });

  test('updates status and priority then reloads the current page', () async {
    final repository = _FakeIncidentRepository();
    final controller = IncidentController(repository);
    await controller.loadIncidents();

    await controller.updateIncident(
      incident: controller.incidents.single,
      priority: 'CRITICA',
      status: 'EN_ATENCION',
    );

    expect(repository.updatedPriority, 'CRITICA');
    expect(repository.updatedStatus, 'EN_ATENCION');
    expect(repository.loadCalls, 2);

    controller.dispose();
  });

  test('changing a filter preserves the other active filters', () async {
    final repository = _FakeIncidentRepository();
    final controller = IncidentController(repository);

    await controller.loadIncidents(
      search: 'mesa',
      priority: 'ALTA',
      status: 'ABIERTA',
    );
    await controller.loadIncidents(status: 'RESUELTA');

    expect(repository.lastSearch, 'mesa');
    expect(repository.lastPriority, 'ALTA');
    expect(repository.lastStatus, 'RESUELTA');

    controller.dispose();
  });
}

class _FakeIncidentRepository implements IncidentRepository {
  _FakeIncidentRepository({this.reportError});

  final ApiException? reportError;
  int loadCalls = 0;
  int reportCalls = 0;
  String? updatedPriority;
  String? updatedStatus;
  String? lastSearch;
  String? lastPriority;
  String? lastStatus;

  @override
  Future<IncidentPage> getIncidents({
    required int page,
    String? search,
    String? priority,
    String? status,
  }) async {
    loadCalls++;
    lastSearch = search;
    lastPriority = priority;
    lastStatus = status;
    return IncidentPage(
      incidents: [_incident()],
      currentPage: 1,
      lastPage: 1,
      total: 1,
    );
  }

  @override
  Future<Incident> reportIncident({
    required String title,
    required String description,
    required String priority,
  }) async {
    reportCalls++;
    if (reportError case final error?) {
      throw error;
    }

    return _incident();
  }

  @override
  Future<Incident> updateIncident({
    required int incidentId,
    required String priority,
    required String status,
  }) async {
    updatedPriority = priority;
    updatedStatus = status;
    return _incident(priority: priority, status: status);
  }
}

Incident _incident({String priority = 'MEDIA', String status = 'ABIERTA'}) =>
    Incident(
      id: 73,
      title: 'Mesa dañada',
      description: 'Una pata está suelta.',
      priority: priority,
      status: status,
      reporterId: 2,
      reporterName: 'Ana Pérez',
    );
