import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/features/auth/models/auth_user.dart';
import 'package:hostalconnect_mobile/features/auth/providers/auth_controller.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/orders/models/order.dart';
import 'package:hostalconnect_mobile/features/orders/providers/order_controller.dart';
import 'package:hostalconnect_mobile/features/orders/repositories/order_repository.dart';
import 'package:hostalconnect_mobile/features/orders/screens/order_history_screen.dart';
import 'package:hostalconnect_mobile/features/payments/models/payment.dart';
import 'package:hostalconnect_mobile/features/payments/providers/payment_controller.dart';
import 'package:hostalconnect_mobile/features/payments/repositories/payment_repository.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets(
    'staff can advance order status without customer payment actions',
    (tester) async {
      final orderRepository = _FakeOrderRepository();
      final paymentRepository = _FakePaymentRepository();
      final authController = AuthController(
        _FakeAuthRepository(role: 'OPERADOR'),
      );
      final orderController = OrderController(orderRepository);
      final paymentController = PaymentController(paymentRepository);
      await authController.restoreSession();

      await tester.pumpWidget(
        MultiProvider(
          providers: [
            ChangeNotifierProvider<AuthController>.value(value: authController),
            ChangeNotifierProvider<OrderController>.value(
              value: orderController,
            ),
            ChangeNotifierProvider<PaymentController>.value(
              value: paymentController,
            ),
          ],
          child: const MaterialApp(home: OrderHistoryScreen()),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Gestión de pedidos'), findsOneWidget);
      await tester.tap(find.text('Pedido #123 · Registrado'));
      await tester.pumpAndSettle();

      expect(find.textContaining('Ana Pérez'), findsOneWidget);
      expect(find.byKey(const Key('advance-order-123')), findsOneWidget);
      expect(find.text('Iniciar preparación'), findsOneWidget);
      expect(find.text('Pagos'), findsNothing);
      expect(find.text('Solicitar pago en línea'), findsNothing);

      await tester.tap(find.byKey(const Key('advance-order-123')));
      await tester.pumpAndSettle();

      expect(orderRepository.changedStatus, 'EN_PREPARACION');
      expect(orderController.orders.single.status, 'EN_PREPARACION');
      expect(paymentRepository.loadCalls, 0);

      authController.dispose();
      orderController.dispose();
      paymentController.dispose();
    },
  );

  testWidgets('customers retain payment and cancellation actions only', (
    tester,
  ) async {
    final orderRepository = _FakeOrderRepository();
    final paymentRepository = _FakePaymentRepository();
    final authController = AuthController(_FakeAuthRepository(role: 'CLIENTE'));
    final orderController = OrderController(orderRepository);
    final paymentController = PaymentController(paymentRepository);
    await authController.restoreSession();

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: authController),
          ChangeNotifierProvider<OrderController>.value(value: orderController),
          ChangeNotifierProvider<PaymentController>.value(
            value: paymentController,
          ),
        ],
        child: const MaterialApp(home: OrderHistoryScreen()),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Pedido #123 · Registrado'));
    await tester.pumpAndSettle();

    expect(find.text('Pedidos'), findsOneWidget);
    expect(find.byKey(const Key('advance-order-123')), findsNothing);
    expect(find.text('Solicitar cancelación'), findsOneWidget);
    expect(find.text('Solicitar pago en línea'), findsOneWidget);
    expect(paymentRepository.loadCalls, 1);

    authController.dispose();
    orderController.dispose();
    paymentController.dispose();
  });
}

class _FakeAuthRepository implements AuthRepository {
  _FakeAuthRepository({required this.role});

  final String role;

  @override
  Future<void> clearToken() async {}

  @override
  Future<AuthUser> login(String correo, String password) async =>
      _createUser(role);

  @override
  Future<void> logout() async {}

  @override
  Future<AuthUser?> restoreSession() async => _createUser(role);
}

AuthUser _createUser(String role) => AuthUser(
  id: 1,
  nombres: 'Test',
  apellidos: 'User',
  correo: 'test@example.test',
  rol: role,
);

class _FakeOrderRepository implements OrderRepository {
  String? changedStatus;
  CustomerOrder _order = const CustomerOrder(
    id: 123,
    status: 'REGISTRADO',
    attentionType: 'INMEDIATO',
    serviceMode: 'SIN_MESA',
    total: 12500,
    customerName: 'Ana Pérez',
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

  @override
  Future<OrderPage> getOrders({required int page, String? status}) async =>
      OrderPage(orders: [_order], currentPage: 1, lastPage: 1, total: 1);

  @override
  Future<CustomerOrder> createOrder({
    required String attentionType,
    required String serviceMode,
    DateTime? scheduledAt,
    required List<Map<String, Object?>> lines,
  }) async => _order;

  @override
  Future<CustomerOrder> cancelOrder(int orderId) async => _order;

  @override
  Future<CustomerOrder> changeOrderStatus(int orderId, String status) async {
    changedStatus = status;
    _order = CustomerOrder(
      id: _order.id,
      status: status,
      attentionType: _order.attentionType,
      serviceMode: _order.serviceMode,
      total: _order.total,
      lines: _order.lines,
      customerName: _order.customerName,
    );

    return _order;
  }
}

class _FakePaymentRepository implements PaymentRepository {
  int loadCalls = 0;

  @override
  Future<List<Payment>> getForOrder(int orderId) async {
    loadCalls++;
    return const [];
  }

  @override
  Future<Payment> requestOnlinePayment(
    int orderId,
    String idempotencyKey,
  ) async {
    throw UnimplementedError();
  }

  @override
  Future<Payment> registerInPersonPayment(
    int orderId,
    String idempotencyKey,
  ) async {
    throw UnimplementedError();
  }
}
