import '../../../core/network/api_exception.dart';

class OrderLine {
  const OrderLine({
    required this.productId,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
  });

  factory OrderLine.fromJson(Map<String, dynamic> json) {
    final productId = json['producto_id'];
    final rawProduct = json['producto'];
    final name = rawProduct is Map<String, dynamic>
        ? rawProduct['nombre']
        : null;
    final quantity = json['cantidad'];
    final unitPrice = _parseMoney(json['precio_unitario']);
    final subtotal = _parseMoney(json['subtotal']);

    if (productId is! int ||
        name is! String ||
        quantity is! int ||
        unitPrice == null ||
        subtotal == null) {
      throw const ApiException(
        message: 'El servidor devolvió un detalle de pedido no válido.',
      );
    }

    return OrderLine(
      productId: productId,
      name: name,
      quantity: quantity,
      unitPrice: unitPrice,
      subtotal: subtotal,
    );
  }

  final int productId;
  final String name;
  final int quantity;
  final double unitPrice;
  final double subtotal;
}

class CustomerOrder {
  const CustomerOrder({
    required this.id,
    required this.status,
    required this.attentionType,
    required this.serviceMode,
    required this.total,
    required this.lines,
    this.scheduledAt,
  });

  factory CustomerOrder.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final status = json['estado'];
    final attentionType = json['tipo_atencion'];
    final serviceMode = json['modalidad_servicio'];
    final total = _parseMoney(json['total']);
    final rawLines = json['detalles'];
    final scheduledAt = json['fecha_programada'];

    if (id is! int ||
        status is! String ||
        attentionType is! String ||
        serviceMode is! String ||
        total == null ||
        rawLines is! List ||
        (scheduledAt != null && scheduledAt is! String)) {
      throw const ApiException(
        message: 'El servidor devolvió un pedido no válido.',
      );
    }

    return CustomerOrder(
      id: id,
      status: status,
      attentionType: attentionType,
      serviceMode: serviceMode,
      total: total,
      scheduledAt: scheduledAt is String
          ? DateTime.tryParse(scheduledAt)
          : null,
      lines: rawLines
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message: 'El servidor devolvió un detalle de pedido no válido.',
              );
            }

            return OrderLine.fromJson(item);
          })
          .toList(growable: false),
    );
  }

  final int id;
  final String status;
  final String attentionType;
  final String serviceMode;
  final double total;
  final List<OrderLine> lines;
  final DateTime? scheduledAt;

  bool get canRequestCancellation => status == 'REGISTRADO';
}

class OrderPage {
  const OrderPage({
    required this.orders,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory OrderPage.fromJson(Map<String, dynamic> json) {
    final rawOrders = json['data'];
    final rawMeta = json['meta'];
    if (rawOrders is! List || rawMeta is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de pedidos no válida.',
      );
    }

    final currentPage = rawMeta['current_page'];
    final lastPage = rawMeta['last_page'];
    final total = rawMeta['total'];
    if (currentPage is! int || lastPage is! int || total is! int) {
      throw const ApiException(
        message: 'El servidor devolvió metadatos de pedidos no válidos.',
      );
    }

    return OrderPage(
      orders: rawOrders
          .map((item) {
            if (item is! Map<String, dynamic>) {
              throw const ApiException(
                message: 'El servidor devolvió un pedido no válido.',
              );
            }

            return CustomerOrder.fromJson(item);
          })
          .toList(growable: false),
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
    );
  }

  final List<CustomerOrder> orders;
  final int currentPage;
  final int lastPage;
  final int total;
}

double? _parseMoney(Object? value) {
  if (value is num) {
    return value.toDouble();
  }

  return value is String ? double.tryParse(value) : null;
}
