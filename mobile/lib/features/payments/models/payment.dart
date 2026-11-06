import '../../../core/network/api_exception.dart';

class Payment {
  const Payment({
    required this.id,
    required this.orderId,
    required this.amount,
    required this.channel,
    required this.status,
    required this.reference,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final orderId = json['pedido_id'];
    final amount = _parseMoney(json['monto']);
    final channel = json['canal'];
    final status = json['estado'];
    final reference = json['referencia_operacion'];

    if (id is! int ||
        orderId is! int ||
        amount == null ||
        channel is! String ||
        status is! String ||
        reference is! String) {
      throw const ApiException(
        message: 'El servidor devolvió un pago con formato no válido.',
      );
    }

    return Payment(
      id: id,
      orderId: orderId,
      amount: amount,
      channel: channel,
      status: status,
      reference: reference,
    );
  }

  final int id;
  final int orderId;
  final double amount;
  final String channel;
  final String status;
  final String reference;

  bool get isPending => status == 'PENDIENTE';
}

double? _parseMoney(Object? value) {
  if (value is num) {
    return value.toDouble();
  }

  return value is String ? double.tryParse(value) : null;
}
