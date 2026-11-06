import '../../../core/network/api_exception.dart';
import '../../../core/network/base_repository.dart';
import '../models/order.dart';

abstract interface class OrderRepository {
  Future<OrderPage> getOrders({required int page, String? status});

  Future<CustomerOrder> createOrder({
    required String attentionType,
    required String serviceMode,
    DateTime? scheduledAt,
    required List<Map<String, Object?>> lines,
  });

  Future<CustomerOrder> cancelOrder(int orderId);
}

class ApiOrderRepository extends BaseRepository implements OrderRepository {
  const ApiOrderRepository(super.apiClient);

  @override
  Future<OrderPage> getOrders({required int page, String? status}) async {
    final queryParameters = {'page': '$page', 'per_page': '10'};
    if (status != null) {
      queryParameters['estado'] = status;
    }

    final response = await get('pedidos', queryParameters: queryParameters);

    return OrderPage.fromJson(response);
  }

  @override
  Future<CustomerOrder> createOrder({
    required String attentionType,
    required String serviceMode,
    DateTime? scheduledAt,
    required List<Map<String, Object?>> lines,
  }) async {
    final response = await post(
      'pedidos',
      body: {
        'tipo_atencion': attentionType,
        'modalidad_servicio': serviceMode,
        if (scheduledAt != null)
          'fecha_programada': _formatDateTime(scheduledAt),
        'lineas': lines,
      },
    );

    return CustomerOrder.fromJson(_object(response['data']));
  }

  @override
  Future<CustomerOrder> cancelOrder(int orderId) async {
    final response = await post('pedidos/$orderId/cancelacion');

    return CustomerOrder.fromJson(_object(response['data']));
  }

  Map<String, dynamic> _object(Object? value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    throw const ApiException(
      message: 'El servidor devolvió un pedido con formato no válido.',
    );
  }

  String _formatDateTime(DateTime value) {
    final local = value.toLocal();
    final date =
        '${local.year.toString().padLeft(4, '0')}-'
        '${local.month.toString().padLeft(2, '0')}-'
        '${local.day.toString().padLeft(2, '0')}';
    final time =
        '${local.hour.toString().padLeft(2, '0')}:'
        '${local.minute.toString().padLeft(2, '0')}';

    return '${date}T$time';
  }
}
