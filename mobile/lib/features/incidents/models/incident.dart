import '../../../core/network/api_exception.dart';

class Incident {
  const Incident({
    required this.id,
    required this.title,
    required this.description,
    required this.priority,
    required this.status,
    this.reporterId,
    this.reporterName,
  });

  factory Incident.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final title = json['titulo'];
    final description = json['descripcion'];
    final priority = json['prioridad'];
    final status = json['estado'];
    final reporterId = json['usuario_reporta_id'];
    final rawReporter = json['usuario'];
    final reporterName = rawReporter is Map<String, dynamic>
        ? _reporterName(rawReporter)
        : null;

    if (id is! int ||
        title is! String ||
        description is! String ||
        priority is! String ||
        status is! String ||
        (reporterId != null && reporterId is! int)) {
      throw const ApiException(
        message: 'El servidor devolvió una incidencia con formato no válido.',
      );
    }

    return Incident(
      id: id,
      title: title,
      description: description,
      priority: priority,
      status: status,
      reporterId: reporterId is int ? reporterId : null,
      reporterName: reporterName,
    );
  }

  static String? _reporterName(Map<String, dynamic> reporter) {
    final firstName = reporter['nombres'];
    final lastName = reporter['apellidos'];
    if (firstName is String && lastName is String) {
      return '$firstName $lastName';
    }

    return null;
  }

  final int id;
  final String title;
  final String description;
  final String priority;
  final String status;
  final int? reporterId;
  final String? reporterName;
}

class IncidentPage {
  const IncidentPage({
    required this.incidents,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory IncidentPage.fromJson(Map<String, dynamic> json) {
    final rawIncidents = json['data'];
    final rawMeta = json['meta'];
    if (rawIncidents is! List || rawMeta is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de incidencias no válida.',
      );
    }

    final currentPage = rawMeta['current_page'];
    final lastPage = rawMeta['last_page'];
    final total = rawMeta['total'];
    if (currentPage is! int || lastPage is! int || total is! int) {
      throw const ApiException(
        message: 'El servidor devolvió metadatos de incidencias no válidos.',
      );
    }

    return IncidentPage(
      incidents: rawIncidents
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message:
                    'El servidor devolvió una incidencia con formato no válido.',
              );
            }

            return Incident.fromJson(item);
          })
          .toList(growable: false),
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
    );
  }

  final List<Incident> incidents;
  final int currentPage;
  final int lastPage;
  final int total;
}
