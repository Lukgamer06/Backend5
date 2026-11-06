import '../../../core/network/api_exception.dart';

class Product {
  const Product({
    required this.id,
    required this.name,
    required this.price,
    required this.available,
    this.description,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final name = json['nombre'];
    final description = json['descripcion'];
    final price = json['precio'];
    final available = json['disponible'];
    final parsedPrice = price is num
        ? price.toDouble()
        : double.tryParse('$price');

    if (id is! int ||
        name is! String ||
        (description != null && description is! String) ||
        parsedPrice == null ||
        available is! bool) {
      throw const ApiException(
        message: 'El servidor devolvió un producto no válido.',
      );
    }

    return Product(
      id: id,
      name: name,
      description: description is String ? description : null,
      price: parsedPrice,
      available: available,
    );
  }

  final int id;
  final String name;
  final String? description;
  final double price;
  final bool available;
}
