import '../../../core/network/api_exception.dart';
import 'daily_menu.dart';

class MenuPage {
  const MenuPage({
    required this.menus,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory MenuPage.fromJson(Map<String, dynamic> json) {
    final rawMenus = json['data'];
    final rawMeta = json['meta'];
    if (rawMenus is! List || rawMeta is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de menús no válida.',
      );
    }

    final currentPage = rawMeta['current_page'];
    final lastPage = rawMeta['last_page'];
    final total = rawMeta['total'];
    if (currentPage is! int || lastPage is! int || total is! int) {
      throw const ApiException(
        message: 'El servidor devolvió metadatos de menús no válidos.',
      );
    }

    return MenuPage(
      menus: rawMenus
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message: 'El servidor devolvió un menú con formato no válido.',
              );
            }

            return DailyMenu.fromJson(item);
          })
          .toList(growable: false),
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
    );
  }

  final List<DailyMenu> menus;
  final int currentPage;
  final int lastPage;
  final int total;
}
