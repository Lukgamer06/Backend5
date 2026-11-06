import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/catalog/models/daily_menu.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product_page.dart';
import 'package:hostalconnect_mobile/features/catalog/providers/catalog_controller.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';

void main() {
  test('search resets pagination and loads the requested result set', () async {
    final repository = _FakeCatalogRepository();
    final controller = CatalogController(repository);
    await controller.loadProducts(page: 2);

    await controller.loadProducts(search: ' sopa ');

    expect(repository.lastPageRequested, 1);
    expect(repository.lastSearch, 'sopa');
    expect(controller.currentPage, 1);
    expect(controller.products.single.name, 'Sopa');

    controller.dispose();
  });

  test('missing daily menu is an empty state, not a catalog failure', () async {
    final controller = CatalogController(
      _FakeCatalogRepository(
        menuError: const ApiException(
          message: 'Recurso no encontrado.',
          statusCode: 404,
        ),
      ),
    );

    await controller.loadDailyMenu();

    expect(controller.dailyMenu, isNull);
    expect(controller.menuError, isNull);

    controller.dispose();
  });
}

class _FakeCatalogRepository implements CatalogRepository {
  _FakeCatalogRepository({this.menuError});

  final ApiException? menuError;
  int? lastPageRequested;
  String? lastSearch;

  @override
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  }) async {
    lastPageRequested = page;
    lastSearch = search;

    return ProductPage(
      products: [
        Product(
          id: 1,
          name: search == null || search.isEmpty ? 'Producto' : 'Sopa',
          price: 1200,
          available: true,
        ),
      ],
      currentPage: page,
      lastPage: 3,
      total: 25,
    );
  }

  @override
  Future<DailyMenu> getDailyMenu() async {
    if (menuError case final error?) {
      throw error;
    }

    return DailyMenu(
      id: 1,
      date: DateTime(2026, 11, 5),
      status: 'PUBLICADO',
      products: [],
    );
  }
}
