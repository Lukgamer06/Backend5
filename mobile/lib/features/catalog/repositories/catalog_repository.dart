import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/daily_menu.dart';
import '../models/product_page.dart';

abstract interface class CatalogRepository {
  Future<ProductPage> getProducts({
    required int page,
    required int perPage,
    String? search,
  });

  Future<DailyMenu> getDailyMenu();
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
}
