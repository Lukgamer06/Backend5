import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/catalog/models/product.dart';
import 'package:hostalconnect_mobile/features/orders/models/order.dart';
import 'package:hostalconnect_mobile/features/orders/providers/order_controller.dart';
import 'package:hostalconnect_mobile/features/orders/repositories/order_repository.dart';

void main() {
  test('cart updates quantities and omits unavailable products', () async {
    final controller = OrderController(_FakeOrderRepository());
    const available = Product(
      id: 1,
      name: 'Sopa',
      price: 12000,
      available: true,
    );
    const unavailable = Product(
      id: 2,
      name: 'Agotado',
      price: 1000,
      available: false,
    );

    controller.addProduct(available);
    controller.addProduct(available);
    controller.addProduct(unavailable);
    expect(controller.cartItemCount, 2);

    controller.decreaseProduct(available.id);
    expect(controller.cartItemCount, 1);
    controller.decreaseProduct(available.id);
    expect(controller.cartItems, isEmpty);

    controller.dispose();
  });

  test(
    'order submission leaves pricing to server and clears cart on success',
    () async {
      final repository = _FakeOrderRepository();
      final controller = OrderController(repository);
      controller.addProduct(
        const Product(id: 1, name: 'Sopa', price: 12000, available: true),
      );

      final order = await controller.submitOrder(scheduled: false);

      expect(order?.total, 12500);
      expect(repository.attentionType, 'INMEDIATO');
      expect(repository.serviceMode, 'SIN_MESA');
      expect(repository.lines, [
        {'producto_id': 1, 'cantidad': 1},
      ]);
      expect(controller.cartItemCount, 0);

      controller.dispose();
    },
  );

  test('scheduled order requires a future time', () async {
    final repository = _FakeOrderRepository();
    final controller = OrderController(repository);
    controller.addProduct(
      const Product(id: 1, name: 'Sopa', price: 12000, available: true),
    );

    final result = await controller.submitOrder(
      scheduled: true,
      scheduledAt: DateTime.now().subtract(const Duration(minutes: 1)),
    );

    expect(result, isNull);
    expect(repository.createCalls, 0);
    expect(controller.error, contains('fecha y hora futura'));

    controller.dispose();
  });

  test(
    'failed cancellation shows API conflict without altering the order',
    () async {
      final repository = _FakeOrderRepository(
        cancellationError: const ApiException(
          message: 'El pedido no se puede cancelar.',
          statusCode: 403,
        ),
      );
      final controller = OrderController(repository);

      await controller.cancelOrder(_FakeOrderRepository.order);

      expect(controller.error, 'El pedido no se puede cancelar.');

      controller.dispose();
    },
  );
}

class _FakeOrderRepository implements OrderRepository {
  _FakeOrderRepository({this.cancellationError});

  static const order = CustomerOrder(
    id: 123,
    status: 'REGISTRADO',
    attentionType: 'INMEDIATO',
    serviceMode: 'SIN_MESA',
    total: 12500,
    lines: [
      OrderLine(
        productId: 1,
        name: 'Sopa',
        quantity: 1,
        unitPrice: 12500,
        subtotal: 12500,
      ),
    ],
  );

  final ApiException? cancellationError;
  String? attentionType;
  String? serviceMode;
  List<Map<String, Object?>>? lines;
  int createCalls = 0;

  @override
  Future<OrderPage> getOrders({required int page, String? status}) async {
    return const OrderPage(
      orders: [order],
      currentPage: 1,
      lastPage: 1,
      total: 1,
    );
  }

  @override
  Future<CustomerOrder> createOrder({
    required String attentionType,
    required String serviceMode,
    DateTime? scheduledAt,
    required List<Map<String, Object?>> lines,
  }) async {
    createCalls++;
    this.attentionType = attentionType;
    this.serviceMode = serviceMode;
    this.lines = lines;

    return order;
  }

  @override
  Future<CustomerOrder> cancelOrder(int orderId) async {
    if (cancellationError case final error?) {
      throw error;
    }

    return order;
  }
}
