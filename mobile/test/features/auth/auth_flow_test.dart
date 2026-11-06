import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/auth/models/auth_user.dart';
import 'package:hostalconnect_mobile/features/auth/providers/auth_controller.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/auth/screens/home_screen.dart';
import 'package:hostalconnect_mobile/features/auth/screens/login_screen.dart';
import 'package:hostalconnect_mobile/features/catalog/models/daily_menu.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product_page.dart';
import 'package:hostalconnect_mobile/features/catalog/providers/catalog_controller.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';
import 'package:hostalconnect_mobile/routes/app_router.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('login navigates to the protected home screen', (tester) async {
    final controller = AuthController(_FakeAuthRepository());
    await controller.restoreSession();
    final catalogController = CatalogController(_FakeCatalogRepository());
    await catalogController.load();
    final router = createAppRouter(controller);

    await tester.pumpWidget(
      ChangeNotifierProvider<AuthController>.value(
        value: controller,
        child: ChangeNotifierProvider<CatalogController>.value(
          value: catalogController,
          child: MaterialApp.router(routerConfig: router),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byType(LoginScreen), findsOneWidget);

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Correo electrónico'),
      'ana@example.test',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Contraseña'),
      'secret',
    );
    await tester.tap(find.text('Iniciar sesión'));
    await tester.pumpAndSettle();

    expect(find.byType(HomeScreen), findsOneWidget);
    expect(find.text('Hola, Ana'), findsOneWidget);
    expect(find.text('Sopa del día'), findsOneWidget);
    expect(find.text('Menú del día'), findsOneWidget);

    await tester.tap(find.text('Cerrar sesión'));
    await tester.pumpAndSettle();

    expect(find.byType(LoginScreen), findsOneWidget);

    router.dispose();
    controller.dispose();
    catalogController.dispose();
  });

  testWidgets('unauthenticated users cannot remain on the protected route', (
    tester,
  ) async {
    final controller = AuthController(_FakeAuthRepository());
    await controller.restoreSession();
    final catalogController = CatalogController(_FakeCatalogRepository());
    await catalogController.load();
    final router = createAppRouter(controller);

    await tester.pumpWidget(
      ChangeNotifierProvider<AuthController>.value(
        value: controller,
        child: ChangeNotifierProvider<CatalogController>.value(
          value: catalogController,
          child: MaterialApp.router(routerConfig: router),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byType(LoginScreen), findsOneWidget);
    expect(find.byType(HomeScreen), findsNothing);

    router.dispose();
    controller.dispose();
    catalogController.dispose();
  });

  test(
    'expired session clears the token and returns to unauthenticated state',
    () async {
      final repository = _FakeAuthRepository(
        restoreError: const ApiException(
          message: 'La sesión no está autenticada o ha expirado.',
          statusCode: 401,
        ),
      );
      final controller = AuthController(repository);

      await controller.restoreSession();

      expect(controller.status, AuthStatus.unauthenticated);
      expect(controller.user, isNull);
      expect(repository.clearTokenCalls, 1);
      expect(controller.message, contains('expiró'));

      controller.dispose();
    },
  );
}

class _FakeCatalogRepository implements CatalogRepository {
  @override
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  }) async {
    return const ProductPage(
      products: [
        Product(
          id: 1,
          name: 'Sopa del día',
          description: 'Sopa casera',
          price: 12500,
          available: true,
        ),
      ],
      currentPage: 1,
      lastPage: 1,
      total: 1,
    );
  }

  @override
  Future<DailyMenu> getDailyMenu() async {
    return DailyMenu(
      id: 1,
      date: DateTime(2026, 11, 5),
      status: 'PUBLICADO',
      products: const [
        Product(
          id: 1,
          name: 'Sopa del día',
          description: 'Sopa casera',
          price: 12500,
          available: true,
        ),
      ],
    );
  }
}

class _FakeAuthRepository implements AuthRepository {
  _FakeAuthRepository({this.restoreError});

  static const _user = AuthUser(
    id: 1,
    nombres: 'Ana',
    apellidos: 'Pérez',
    correo: 'ana@example.test',
    rol: 'CLIENTE',
  );

  final ApiException? restoreError;
  int clearTokenCalls = 0;

  @override
  Future<void> clearToken() async {
    clearTokenCalls++;
  }

  @override
  Future<AuthUser> login(String correo, String password) async => _user;

  @override
  Future<void> logout() async {}

  @override
  Future<AuthUser?> restoreSession() async {
    if (restoreError case final error?) {
      throw error;
    }

    return null;
  }
}
