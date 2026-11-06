import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/features/auth/models/auth_user.dart';
import 'package:hostalconnect_mobile/features/auth/providers/auth_controller.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/incidents/models/incident.dart';
import 'package:hostalconnect_mobile/features/incidents/providers/incident_controller.dart';
import 'package:hostalconnect_mobile/features/incidents/repositories/incident_repository.dart';
import 'package:hostalconnect_mobile/features/incidents/screens/incident_screen.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('staff can report and manage incidents', (tester) async {
    final authController = AuthController(_FakeAuthRepository());
    await authController.restoreSession();
    final incidentController = IncidentController(_FakeIncidentRepository());

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: authController),
          ChangeNotifierProvider<IncidentController>.value(
            value: incidentController,
          ),
        ],
        child: const MaterialApp(home: IncidentScreen()),
      ),
    );
    await tester.pumpAndSettle();

    await tester.enterText(
      find.byKey(const Key('incident-title')),
      'Mesa dañada',
    );
    await tester.enterText(
      find.byKey(const Key('incident-description')),
      'Una pata está suelta.',
    );
    await tester.tap(find.byKey(const Key('report-incident')));
    await tester.pumpAndSettle();

    expect(find.text('Incidencia #73 reportada.'), findsOneWidget);
    expect(find.text('Mesa dañada'), findsOneWidget);
    expect(find.text('Reportada por: Luis Operador'), findsOneWidget);

    authController.dispose();
    incidentController.dispose();
  });

  testWidgets('customers cannot view staff incident management', (
    tester,
  ) async {
    final authController = AuthController(_FakeCustomerAuthRepository());
    await authController.restoreSession();
    final repository = _FakeIncidentRepository();
    final incidentController = IncidentController(repository);

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: authController),
          ChangeNotifierProvider<IncidentController>.value(
            value: incidentController,
          ),
        ],
        child: const MaterialApp(home: IncidentScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.text('La gestión de incidencias está disponible para personal.'),
      findsOneWidget,
    );
    expect(find.byKey(const Key('report-incident')), findsNothing);
    expect(repository.loadCalls, 0);

    authController.dispose();
    incidentController.dispose();
  });
}

class _FakeAuthRepository implements AuthRepository {
  static const _user = AuthUser(
    id: 1,
    nombres: 'Luis',
    apellidos: 'Operador',
    correo: 'luis@example.test',
    rol: 'OPERADOR',
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

class _FakeCustomerAuthRepository extends _FakeAuthRepository {
  static const _customer = AuthUser(
    id: 2,
    nombres: 'Ana',
    apellidos: 'Pérez',
    correo: 'ana@example.test',
    rol: 'CLIENTE',
  );

  @override
  Future<AuthUser?> restoreSession() async => _customer;
}

class _FakeIncidentRepository implements IncidentRepository {
  int loadCalls = 0;

  @override
  Future<IncidentPage> getIncidents({
    required int page,
    String? search,
    String? priority,
    String? status,
  }) async {
    loadCalls++;

    return const IncidentPage(
      incidents: [
        Incident(
          id: 73,
          title: 'Mesa dañada',
          description: 'Una pata está suelta.',
          priority: 'MEDIA',
          status: 'ABIERTA',
          reporterId: 1,
          reporterName: 'Luis Operador',
        ),
      ],
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
  }) async => Incident(
    id: 73,
    title: title,
    description: description,
    priority: priority,
    status: 'ABIERTA',
    reporterId: 1,
    reporterName: 'Luis Operador',
  );

  @override
  Future<Incident> updateIncident({
    required int incidentId,
    required String priority,
    required String status,
  }) async => Incident(
    id: incidentId,
    title: 'Mesa dañada',
    description: 'Una pata está suelta.',
    priority: priority,
    status: status,
  );
}
