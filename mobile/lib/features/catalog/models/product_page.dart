import '../../../core/network/api_exception.dart';
import 'product.dart';

class ProductPage {
  const ProductPage({
    required this.products,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory ProductPage.fromJson(Map<String, dynamic> json) {
    final rawProducts = json['data'];
    final rawMeta = json['meta'];
    if (rawProducts is! List || rawMeta is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió un catálogo con formato no válido.',
      );
    }

    final currentPage = rawMeta['current_page'];
    final lastPage = rawMeta['last_page'];
    final total = rawMeta['total'];
    if (currentPage is! int || lastPage is! int || total is! int) {
      throw const ApiException(
        message: 'El servidor devolvió metadatos de paginación no válidos.',
      );
    }

    return ProductPage(
      products: rawProducts
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message:
                    'El servidor devolvió un elemento de catálogo no válido.',
              );
            }

            return Product.fromJson(item);
          })
          .toList(growable: false),
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
    );
  }

  final List<Product> products;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasPrevious => currentPage > 1;
  bool get hasNext => currentPage < lastPage;
}
