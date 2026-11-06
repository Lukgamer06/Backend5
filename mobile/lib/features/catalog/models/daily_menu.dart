import '../../../core/network/api_exception.dart';
import 'product.dart';

class DailyMenu {
  const DailyMenu({
    required this.id,
    required this.date,
    required this.status,
    required this.products,
  });

  factory DailyMenu.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final date = json['fecha'];
    final status = json['estado'];
    final rawProducts = json['productos'];

    if (id is! int ||
        date is! String ||
        status is! String ||
        rawProducts is! List) {
      throw const ApiException(
        message: 'El servidor devolvió un menú del día no válido.',
      );
    }

    return DailyMenu(
      id: id,
      date:
          DateTime.tryParse(date) ??
          (throw const ApiException(
            message: 'El servidor devolvió la fecha del menú no válida.',
          )),
      status: status,
      products: rawProducts
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message: 'El servidor devolvió un producto del menú no válido.',
              );
            }

            return Product.fromJson(item);
          })
          .toList(growable: false),
    );
  }

  final int id;
  final DateTime date;
  final String status;
  final List<Product> products;
}
