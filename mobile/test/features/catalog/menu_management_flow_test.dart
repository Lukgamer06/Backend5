import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/features/auth/models/auth_user.dart';
import 'package:hostalconnect_mobile/features/auth/providers/auth_controller.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/catalog/models/daily_menu.dart';
import 'package:hostalconnect_mobile/features/catalog/models/menu_page.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product_page.dart';
import 'package:hostalconnect_mobile/features/catalog/providers/menu_management_controller.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';
import 'package:hostalconnect_mobile/features/catalog/screens/menu_management_screen.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('staff creates and publishes a daily menu', (tester) async {
    final controller = MenuManagementController(_FakeCatalogRepository());
    final authController = AuthController(_FakeAuthRepository());
    await authController.restoreSession();

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: authController),
          ChangeNotifierProvider<MenuManagementController>.value(
            value: controller,
          ),
        ],
        child: const MaterialApp(home: MenuManagementScreen()),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('menu-product-5')));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('save-menu-draft')));
    await tester.tap(find.byKey(const Key('save-menu-draft')));
    await tester.pumpAndSettle();

    expect(find.text('Borrador del 08/11/2026 guardado.'), findsOneWidget);
    await tester.pump(const Duration(seconds: 5));
    await tester.pumpAndSettle();
    await tester.tap(find.text('08/11/2026 · Borrador'));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('publish-menu-8')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('publish-menu-8')));
    await tester.pumpAndSettle();

    expect(find.text('Menú del 08/11/2026 publicado.'), findsOneWidget);
    expect(controller.menus.single.status, 'PUBLICADO');

    authController.dispose();
    controller.dispose();
  });

  testWidgets('customers cannot load or manage menus', (tester) async {
    final controller = MenuManagementController(_FakeCatalogRepository());
    final authController = AuthController(_FakeCustomerAuthRepository());
    await authController.restoreSession();

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: authController),
          ChangeNotifierProvider<MenuManagementController>.value(
            value: controller,
          ),
        ],
        child: const MaterialApp(home: MenuManagementScreen()),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.text('La administración del menú está disponible para personal.'),
      findsOneWidget,
    );
    expect(find.byKey(const Key('save-menu-draft')), findsNothing);
    expect(controller.menus, isEmpty);

    authController.dispose();
    controller.dispose();
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
  static const _user = AuthUser(
    id: 2,
    nombres: 'Ana',
    apellidos: 'Pérez',
    correo: 'ana@example.test',
    rol: 'CLIENTE',
  );

  @override
  Future<AuthUser?> restoreSession() async => _user;
}

class _FakeCatalogRepository implements CatalogRepository {
  DailyMenu _menu = _draft();

  @override
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  }) async =>
      const ProductPage(products: [], currentPage: 1, lastPage: 1, total: 0);

  @override
  Future<DailyMenu> getDailyMenu() async => _menu;

  @override
  Future<MenuPage> getMenus({
    required int page,
    required int perPage,
    String? status,
  }) async => MenuPage(menus: [_menu], currentPage: 1, lastPage: 1, total: 1);

  @override
  Future<List<Product>> getMenuProducts({String? search}) async => const [
    Product(id: 5, name: 'Sopa', price: 12500, available: true),
  ];

  @override
  Future<DailyMenu> createMenu({
    required DateTime date,
    required List<int> productIds,
  }) async {
    _menu = DailyMenu(
      id: 8,
      date: DateTime(2026, 11, 8),
      status: 'BORRADOR',
      products: [
        if (productIds.contains(5))
          const Product(id: 5, name: 'Sopa', price: 12500, available: true),
      ],
    );
    return _menu;
  }

  @override
  Future<DailyMenu> updateMenu({
    required int menuId,
    required DateTime date,
    required List<int> productIds,
  }) async => createMenu(date: date, productIds: productIds);

  @override
  Future<DailyMenu> publishMenu(int menuId) async {
    _menu = DailyMenu(
      id: _menu.id,
      date: _menu.date,
      status: 'PUBLICADO',
      products: _menu.products,
    );
    return _menu;
  }
}

DailyMenu _draft() => DailyMenu(
  id: 8,
  date: DateTime(2026, 11, 8),
  status: 'BORRADOR',
  products: const [],
);
