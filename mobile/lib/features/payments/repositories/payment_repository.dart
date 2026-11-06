import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/payment.dart';

abstract interface class PaymentRepository {
  Future<List<Payment>> getForOrder(int orderId);

  Future<Payment> requestOnlinePayment(int orderId, String idempotencyKey);

  Future<Payment> registerInPersonPayment(int orderId, String idempotencyKey);
}

class ApiPaymentRepository extends BaseRepository implements PaymentRepository {
  const ApiPaymentRepository(super.apiClient);

  @override
  Future<List<Payment>> getForOrder(int orderId) async {
    final response = await get('pedidos/$orderId/pagos');
    final rawPayments = response['data'];
    if (rawPayments is! List) {
      throw const ApiException(
        message: 'El servidor devolvió una lista de pagos no válida.',
      );
    }

    return rawPayments
        .map((item) {
          if (item is! Map<String, dynamic>) {
            throw const ApiException(
              message: 'El servidor devolvió un pago con formato no válido.',
            );
          }

          return Payment.fromJson(item);
        })
        .toList(growable: false);
  }

  @override
  Future<Payment> requestOnlinePayment(int orderId, String idempotencyKey) {
    return _createPayment('pedidos/$orderId/pagos/enlinea', idempotencyKey);
  }

  @override
  Future<Payment> registerInPersonPayment(int orderId, String idempotencyKey) {
    return _createPayment(
      'pedidos/$orderId/pagos/presenciales',
      idempotencyKey,
    );
  }

  Future<Payment> _createPayment(String path, String idempotencyKey) async {
    final response = await post(
      path,
      headers: {'Idempotency-Key': idempotencyKey},
    );
    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const ApiException(
        message: 'El servidor devolvió un pago con formato no válido.',
      );
    }

    return Payment.fromJson(data);
  }
}
