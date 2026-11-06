import '../../../core/network/api_exception.dart';

class AvailableTable {
  const AvailableTable({
    required this.id,
    required this.number,
    required this.capacity,
  });

  factory AvailableTable.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final number = json['numero'];
    final capacity = json['capacidad'];
    if (id is! int || number is! int || capacity is! int) {
      throw const ApiException(
        message: 'El servidor devolvió una mesa con formato no válido.',
      );
    }

    return AvailableTable(id: id, number: number, capacity: capacity);
  }

  final int id;
  final int number;
  final int capacity;
}

class Reservation {
  const Reservation({
    required this.id,
    required this.tableId,
    this.tableNumber,
    required this.startsAt,
    required this.endsAt,
    required this.guestCount,
    required this.status,
    this.notes,
  });

  factory Reservation.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final tableId = json['mesa_id'];
    final rawTable = json['mesa'];
    final tableNumber = rawTable is Map<String, dynamic>
        ? rawTable['numero']
        : null;
    final startsAt = json['fecha_inicio'];
    final endsAt = json['fecha_fin'];
    final guestCount = json['numero_personas'];
    final status = json['estado'];
    final notes = json['observaciones'];
    final parsedStartsAt = startsAt is String
        ? DateTime.tryParse(startsAt)
        : null;
    final parsedEndsAt = endsAt is String ? DateTime.tryParse(endsAt) : null;

    if (id is! int ||
        tableId is! int ||
        parsedStartsAt == null ||
        parsedEndsAt == null ||
        guestCount is! int ||
        status is! String ||
        (notes != null && notes is! String) ||
        (tableNumber != null && tableNumber is! int)) {
      throw const ApiException(
        message: 'El servidor devolvió una reserva con formato no válido.',
      );
    }

    return Reservation(
      id: id,
      tableId: tableId,
      tableNumber: tableNumber as int?,
      startsAt: parsedStartsAt,
      endsAt: parsedEndsAt,
      guestCount: guestCount,
      status: status,
      notes: notes as String?,
    );
  }

  final int id;
  final int tableId;
  final int? tableNumber;
  final DateTime startsAt;
  final DateTime endsAt;
  final int guestCount;
  final String status;
  final String? notes;

  bool get canCancel => status != 'FINALIZADA' && status != 'CANCELADA';
}

class ReservationPage {
  const ReservationPage({
    required this.reservations,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory ReservationPage.fromJson(Map<String, dynamic> json) {
    final rawReservations = json['data'];
    final rawMeta = json['meta'];
    if (rawReservations is! List || rawMeta is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de reservas no válida.',
      );
    }

    final currentPage = rawMeta['current_page'];
    final lastPage = rawMeta['last_page'];
    final total = rawMeta['total'];
    if (currentPage is! int || lastPage is! int || total is! int) {
      throw const ApiException(
        message: 'El servidor devolvió metadatos de reservas no válidos.',
      );
    }

    return ReservationPage(
      reservations: rawReservations
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message:
                    'El servidor devolvió una reserva con formato no válido.',
              );
            }

            return Reservation.fromJson(item);
          })
          .toList(growable: false),
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
    );
  }

  final List<Reservation> reservations;
  final int currentPage;
  final int lastPage;
  final int total;
}
