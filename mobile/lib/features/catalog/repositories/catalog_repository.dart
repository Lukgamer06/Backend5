import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/daily_menu.dart';
import '../models/menu_page.dart';
import '../models/product.dart';
import '../models/product_page.dart';

abstract interface class CatalogRepository {
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  });

  Future<DailyMenu> getDailyMenu();

  Future<MenuPage> getMenus({
    required int page,
    required int perPage,
    String? status,
  });

  Future<List<Product>> getMenuProducts({String? search});

  Future<DailyMenu> createMenu({
    required DateTime date,
    required List<int> productIds,
  });

  Future<DailyMenu> updateMenu({
    required int menuId,
    required DateTime date,
    required List<int> productIds,
  });

  Future<DailyMenu> publishMenu(int menuId);
}

class ApiCatalogRepository extends BaseRepository implements CatalogRepository {
  const ApiCatalogRepository(super.apiClient);

  @override
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  }) async {
    final response = await get(
      'productos',
      queryParameters: {
        'page': '$page',
        'per_page': '$perPage',
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );

    return ProductPage.fromJson(response);
  }

  @override
  Future<DailyMenu> getDailyMenu() async {
    final response = await get('menus-dia/actual');
    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió un menú del día no válido.',
      );
    }

    return DailyMenu.fromJson(data);
  }

  @override
  Future<MenuPage> getMenus({
    required int page,
    required int perPage,
    String? status,
  }) async {
    final response = await get(
      'menus-dia',
      queryParameters: {
        'page': '$page',
        'per_page': '$perPage',
        ...?(status == null ? null : {'estado': status}),
      },
    );

    return MenuPage.fromJson(response);
  }

  @override
  Future<List<Product>> getMenuProducts({String? search}) async {
    final response = await get(
      'productos',
      queryParameters: {
        'page': '1',
        'per_page': '100',
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );
    final rawProducts = response['data'];
    if (rawProducts is! List) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de productos no válida.',
      );
    }

    return rawProducts
        .map((item) {
          if (item is! Map<String, dynamic>) {
            throw const ApiException(
              message: 'El servidor devolvió un producto no válido.',
            );
          }

          return Product.fromJson(item);
        })
        .toList(growable: false);
  }

  @override
  Future<DailyMenu> createMenu({
    required DateTime date,
    required List<int> productIds,
  }) {
    return _saveMenu(
      'menus-dia',
      body: {'fecha': _formatDate(date), 'productos': productIds},
    );
  }

  @override
  Future<DailyMenu> updateMenu({
    required int menuId,
    required DateTime date,
    required List<int> productIds,
  }) {
    return _saveMenu(
      'menus-dia/$menuId',
      method: 'PATCH',
      body: {'fecha': _formatDate(date), 'productos': productIds},
    );
  }

  @override
  Future<DailyMenu> publishMenu(int menuId) async {
    final response = await post('menus-dia/$menuId/publicacion');
    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió un menú del día no válido.',
      );
    }

    return DailyMenu.fromJson(data);
  }

  Future<DailyMenu> _saveMenu(
    String path, {
    String method = 'POST',
    required Map<String, Object> body,
  }) async {
    final response = method == 'POST'
        ? await post(path, body: body)
        : await patch(path, body: body);
    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió un menú del día no válido.',
      );
    }

    return DailyMenu.fromJson(data);
  }

  String _formatDate(DateTime date) =>
      '${date.year.toString().padLeft(4, '0')}-'
      '${date.month.toString().padLeft(2, '0')}-'
      '${date.day.toString().padLeft(2, '0')}';
}
