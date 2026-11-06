import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/incident.dart';

abstract interface class IncidentRepository {
  Future<IncidentPage> getIncidents({
    required int page,
    String? search,
    String? priority,
    String? status,
  });

  Future<Incident> reportIncident({
    required String title,
    required String description,
    required String priority,
  });

  Future<Incident> updateIncident({
    required int incidentId,
    required String priority,
    required String status,
  });
}

class ApiIncidentRepository extends BaseRepository
    implements IncidentRepository {
  const ApiIncidentRepository(super.apiClient);

  @override
  Future<IncidentPage> getIncidents({
    required int page,
    String? search,
    String? priority,
    String? status,
  }) async {
    final trimmedSearch = search?.trim();
    final queryParameters = <String, String>{
      'page': '$page',
      'per_page': '10',
      ...?(trimmedSearch == null || trimmedSearch.isEmpty
          ? null
          : {'search': trimmedSearch}),
      ...?(priority == null ? null : {'prioridad': priority}),
      ...?(status == null ? null : {'estado': status}),
    };

    return IncidentPage.fromJson(
      await get('incidencias', queryParameters: queryParameters),
    );
  }

  @override
  Future<Incident> reportIncident({
    required String title,
    required String description,
    required String priority,
  }) async {
    final response = await post(
      'incidencias',
      body: {
        'titulo': title.trim(),
        'descripcion': description.trim(),
        'prioridad': priority,
      },
    );

    return Incident.fromJson(_object(response['data']));
  }

  @override
  Future<Incident> updateIncident({
    required int incidentId,
    required String priority,
    required String status,
  }) async {
    final response = await patch(
      'incidencias/$incidentId',
      body: {'prioridad': priority, 'estado': status},
    );

    return Incident.fromJson(_object(response['data']));
  }

  Map<String, dynamic> _object(Object? value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    throw const ApiException(
      message: 'El servidor devolvió una incidencia con formato no válido.',
    );
  }
}
