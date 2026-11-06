import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/features/auth/models/auth_user.dart';
import 'package:hostalconnect_mobile/features/auth/providers/auth_controller.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/reservations/models/reservation.dart';
import 'package:hostalconnect_mobile/features/reservations/providers/reservation_controller.dart';
import 'package:hostalconnect_mobile/features/reservations/repositories/reservation_repository.dart';
import 'package:hostalconnect_mobile/features/reservations/screens/reservation_screen.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets(
    'customer checks availability, books, and cancels a reservation',
    (tester) async {
      final authController = AuthController(_FakeAuthRepository());
      await authController.restoreSession();
      final reservationRepository = _FakeReservationRepository();
      final reservationController = ReservationController(
        reservationRepository,
      );

      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<AuthController>.value(value: authController),
            ChangeNotifierProvider<ReservationController>.value(
              value: reservationController,
            ),
          ],
          child: const MaterialApp(home: ReservationScreen()),
        ),
      );
      await tester.pumpAndSettle();

      await tester.tap(find.byKey(const Key('check-table-availability')));
      await tester.pumpAndSettle();
      expect(find.text('Mesa 4'), findsOneWidget);

      await tester.tap(find.byKey(const Key('available-table-8')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('create-reservation')));
      await tester.pumpAndSettle();

      expect(find.text('Reserva #90 · Mesa 4'), findsOneWidget);
      expect(
        tester
            .widget<OutlinedButton>(
              find.byKey(const Key('check-table-availability')),
            )
            .onPressed,
        isNotNull,
      );

      await tester.ensureVisible(
        find.byKey(const Key('cancel-reservation-90')),
      );
      await tester.tap(find.byKey(const Key('cancel-reservation-90')));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Cancelar reserva'));
      await tester.pumpAndSettle();

      expect(find.textContaining('Cancelada ·'), findsOneWidget);

      authController.dispose();
      reservationController.dispose();
    },
  );
}

class _FakeAuthRepository implements AuthRepository {
  static const _user = AuthUser(
    id: 1,
    nombres: 'Ana',
    apellidos: 'Pérez',
    correo: 'ana@example.test',
    rol: 'CLIENTE',
  );

  @override
  Future<void> clearToken() async {}

  @override
  Future<AuthUser> login(String correo, String password) async => _user;

  @override
  Future<void> logout() async {}

  @override
  Future<AuthUser?> restoreSession() async => _user;
}

class _FakeReservationRepository implements ReservationRepository {
  Reservation? _reservation;

  @override
  Future<List<AvailableTable>> getAvailableTables({
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
  }) async => const [AvailableTable(id: 8, number: 4, capacity: 4)];

  @override
  Future<ReservationPage> getReservations({
    required int page,
    String? status,
  }) async {
    final reservations = _reservation == null
        ? <Reservation>[]
        : [_reservation!];

    return ReservationPage(
      reservations: reservations,
      currentPage: 1,
      lastPage: 1,
      total: reservations.length,
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
    _reservation = Reservation(
      id: 90,
      tableId: tableId,
      tableNumber: 4,
      startsAt: startsAt,
      endsAt: endsAt,
      guestCount: guestCount,
      status: 'CONFIRMADA',
      notes: notes,
    );

    return _reservation!;
  }

  @override
  Future<Reservation> cancelReservation(int reservationId) async {
    final reservation = _reservation!;
    _reservation = Reservation(
      id: reservation.id,
      tableId: reservation.tableId,
      tableNumber: reservation.tableNumber,
      startsAt: reservation.startsAt,
      endsAt: reservation.endsAt,
      guestCount: reservation.guestCount,
      status: 'CANCELADA',
      notes: reservation.notes,
    );

    return _reservation!;
  }
}
