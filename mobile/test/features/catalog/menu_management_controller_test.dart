import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/catalog/models/daily_menu.dart';
import 'package:hostalconnect_mobile/features/catalog/models/menu_page.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product_page.dart';
import 'package:hostalconnect_mobile/features/catalog/providers/menu_management_controller.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';

void main() {
  test('loads menus and products for staff', () async {
    final repository = _FakeCatalogRepository();
    final controller = MenuManagementController(repository);

    await controller.loadMenus(status: 'BORRADOR');
    await controller.loadProducts();

    expect(repository.lastMenuStatus, 'BORRADOR');
    expect(controller.menus.single.id, 8);
    expect(controller.products.single.name, 'Sopa');

    controller.dispose();
  });

  test('saves a draft and reloads the menu list', () async {
    final repository = _FakeCatalogRepository();
    final controller = MenuManagementController(repository);

    final menu = await controller.saveMenu(
      date: DateTime(2026, 11, 8),
      productIds: [5],
    );

    expect(menu?.status, 'BORRADOR');
    expect(repository.createdProductIds, [5]);
    expect(repository.menuLoads, 1);

    controller.dispose();
  });

  test('publishes via API and surfaces business-rule errors', () async {
    final repository = _FakeCatalogRepository(
      publishError: const ApiException(
        message: 'Agrega al menos un producto antes de publicar el menú.',
        statusCode: 422,
      ),
    );
    final controller = MenuManagementController(repository);

    final menu = await controller.publishMenu(8);

    expect(menu, isNull);
    expect(controller.actionError, contains('Agrega al menos un producto'));

    controller.dispose();
  });
}

class _FakeCatalogRepository implements CatalogRepository {
  _FakeCatalogRepository({this.publishError});

  final ApiException? publishError;
  String? lastMenuStatus;
  List<int>? createdProductIds;
  int menuLoads = 0;

  @override
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  }) async =>
      const ProductPage(products: [], currentPage: 1, lastPage: 1, total: 0);

  @override
  Future<DailyMenu> getDailyMenu() async => _menu();

  @override
  Future<MenuPage> getMenus({
    required int page,
    required int perPage,
    String? status,
  }) async {
    menuLoads++;
    lastMenuStatus = status;
    return MenuPage(menus: [_menu()], currentPage: 1, lastPage: 1, total: 1);
  }

  @override
  Future<List<Product>> getMenuProducts({String? search}) async => const [
    Product(id: 5, name: 'Sopa', price: 12500, available: true),
  ];

  @override
  Future<DailyMenu> createMenu({
    required DateTime date,
    required List<int> productIds,
  }) async {
    createdProductIds = productIds;
    return DailyMenu(
      id: 8,
      date: date,
      status: 'BORRADOR',
      products: const [
        Product(id: 5, name: 'Sopa', price: 12500, available: true),
      ],
    );
  }

  @override
  Future<DailyMenu> updateMenu({
    required int menuId,
    required DateTime date,
    required List<int> productIds,
  }) async => _menu();

  @override
  Future<DailyMenu> publishMenu(int menuId) async {
    if (publishError case final error?) {
      throw error;
    }

    return DailyMenu(
      id: menuId,
      date: DateTime(2026, 11, 8),
      status: 'PUBLICADO',
      products: const [
        Product(id: 5, name: 'Sopa', price: 12500, available: true),
      ],
    );
  }
}

DailyMenu _menu() => DailyMenu(
  id: 8,
  date: DateTime(2026, 11, 8),
  status: 'BORRADOR',
  products: const [Product(id: 5, name: 'Sopa', price: 12500, available: true)],
);
