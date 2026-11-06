import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/reservations/models/reservation.dart';
import 'package:hostalconnect_mobile/features/reservations/providers/reservation_controller.dart';
import 'package:hostalconnect_mobile/features/reservations/repositories/reservation_repository.dart';

void main() {
  test(
    'creation requires a current availability result and refreshes bookings',
    () async {
      final repository = _FakeReservationRepository();
      final controller = ReservationController(repository);
      final startsAt = DateTime.now().add(const Duration(days: 1));
      final endsAt = startsAt.add(const Duration(hours: 2));

      final withoutAvailability = await controller.createReservation(
        tableId: 8,
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: 3,
      );
      expect(withoutAvailability, isNull);
      expect(repository.createCalls, 0);

      await controller.loadAvailableTables(
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: 3,
      );
      final reservation = await controller.createReservation(
        tableId: 8,
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: 3,
      );

      expect(reservation?.id, 90);
      expect(repository.createCalls, 1);
      expect(repository.reservationLoads, 1);
      expect(controller.reservations.single.tableNumber, 4);
      expect(controller.availableTables, isEmpty);

      controller.dispose();
    },
  );

  test(
    '409 conflict refreshes tables and keeps the conflict visible',
    () async {
      final repository = _FakeReservationRepository(
        createError: const ApiException(
          message: 'La mesa ya no está disponible.',
          statusCode: 409,
        ),
      );
      final controller = ReservationController(repository);
      final startsAt = DateTime.now().add(const Duration(days: 1));
      final endsAt = startsAt.add(const Duration(hours: 2));
      await controller.loadAvailableTables(
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: 3,
      );

      final reservation = await controller.createReservation(
        tableId: 8,
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: 3,
      );

      expect(reservation, isNull);
      expect(controller.actionError, 'La mesa ya no está disponible.');
      expect(repository.availabilityLoads, 2);
      expect(controller.availableTables.single.id, 8);

      controller.dispose();
    },
  );

  test(
    'cancels eligible reservations and blocks final or cancelled ones',
    () async {
      final repository = _FakeReservationRepository();
      final controller = ReservationController(repository);

      await controller.cancelReservation(_reservation(status: 'FINALIZADA'));
      await controller.cancelReservation(_reservation(status: 'CANCELADA'));
      expect(repository.cancellationCalls, 0);

      await controller.cancelReservation(_reservation());

      expect(repository.cancellationCalls, 1);
      expect(repository.reservationLoads, 1);

      controller.dispose();
    },
  );
}

Reservation _reservation({String status = 'CONFIRMADA'}) => Reservation(
  id: 90,
  tableId: 8,
  tableNumber: 4,
  startsAt: DateTime(2026, 12, 10, 18),
  endsAt: DateTime(2026, 12, 10, 19),
  guestCount: 3,
  status: status,
);

class _FakeReservationRepository implements ReservationRepository {
  _FakeReservationRepository({this.createError});

  final ApiException? createError;
  int createCalls = 0;
  int reservationLoads = 0;
  int availabilityLoads = 0;
  int cancellationCalls = 0;

  @override
  Future<List<AvailableTable>> getAvailableTables({
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
  }) async {
    availabilityLoads++;
    return const [AvailableTable(id: 8, number: 4, capacity: 4)];
  }

  @override
  Future<ReservationPage> getReservations({
    required int page,
    String? status,
  }) async {
    reservationLoads++;
    return ReservationPage(
      reservations: [_reservation()],
      currentPage: 1,
      lastPage: 1,
      total: 1,
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
    createCalls++;
    if (createError != null) {
      throw createError!;
    }

    return _reservation();
  }

  @override
  Future<Reservation> cancelReservation(int reservationId) async {
    cancellationCalls++;
    return _reservation(status: 'CANCELADA');
  }
}
