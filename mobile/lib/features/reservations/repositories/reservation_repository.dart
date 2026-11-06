import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/reservation.dart';

abstract interface class ReservationRepository {
  Future<List<AvailableTable>> getAvailableTables({
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
  });

  Future<ReservationPage> getReservations({required int page, String? status});

  Future<Reservation> createReservation({
    required int tableId,
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
    String? notes,
  });

  Future<Reservation> cancelReservation(int reservationId);
}

class ApiReservationRepository extends BaseRepository
    implements ReservationRepository {
  const ApiReservationRepository(super.apiClient);

  @override
  Future<List<AvailableTable>> getAvailableTables({
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
  }) async {
    final response = await get(
      'reservas/disponibilidad',
      queryParameters: {
        'fecha_inicio': _formatDateTime(startsAt),
        'fecha_fin': _formatDateTime(endsAt),
        'numero_personas': '$guestCount',
      },
    );
    final rawTables = response['data'];
    if (rawTables is! List) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de mesas no válida.',
      );
    }

    return rawTables
        .map((item) {
          if (item is! Map<String, dynamic>) {
            throw const ApiException(
              message: 'El servidor devolvió una mesa con formato no válido.',
            );
          }

          return AvailableTable.fromJson(item);
        })
        .toList(growable: false);
  }

  @override
  Future<ReservationPage> getReservations({
    required int page,
    String? status,
  }) async {
    final queryParameters = {'page': '$page', 'per_page': '10'};
    if (status != null) {
      queryParameters['estado'] = status;
    }

    return ReservationPage.fromJson(
      await get('reservas', queryParameters: queryParameters),
    );
  }

  @override
  Future<Reservation> createReservation({
    required int tableId,
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
    String? notes,
  }) async {
    final response = await post(
      'reservas',
      body: {
        'mesa_id': tableId,
        'fecha_inicio': _formatDateTime(startsAt),
        'fecha_fin': _formatDateTime(endsAt),
        'numero_personas': guestCount,
        if (notes != null && notes.isNotEmpty) 'observaciones': notes,
      },
    );

    return Reservation.fromJson(_object(response['data']));
  }

  @override
  Future<Reservation> cancelReservation(int reservationId) async {
    final response = await post('reservas/$reservationId/cancelacion');

    return Reservation.fromJson(_object(response['data']));
  }

  Map<String, dynamic> _object(Object? value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    throw const ApiException(
      message: 'El servidor devolvió una reserva con formato no válido.',
    );
  }

  String _formatDateTime(DateTime value) {
    final local = value.toLocal();
    final date =
        '${local.year.toString().padLeft(4, '0')}-'
        '${local.month.toString().padLeft(2, '0')}-'
        '${local.day.toString().padLeft(2, '0')}';
    final time =
        '${local.hour.toString().padLeft(2, '0')}:'
        '${local.minute.toString().padLeft(2, '0')}';

    return '${date}T$time';
  }
}
