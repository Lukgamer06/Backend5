import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/features/payments/models/payment.dart';
import 'package:hostalconnect_mobile/features/payments/providers/payment_controller.dart';
import 'package:hostalconnect_mobile/features/payments/repositories/payment_repository.dart';

void main() {
  test(
    'reuses an idempotency key after failure and blocks duplicate pending payments',
    () async {
      final repository = _FakePaymentRepository();
      var generatedKeys = 0;
      final controller = PaymentController(
        repository,
        idempotencyKeyGenerator: () => 'key-${++generatedKeys}',
      );
      await controller.loadForOrder(123);

      final failedResult = await controller.requestOnlinePayment(123);
      expect(failedResult, isNull);
      expect(controller.errorFor(123), 'Connection timed out.');

      final payment = await controller.requestOnlinePayment(123);

      expect(payment?.status, 'PENDIENTE');
      expect(repository.onlineKeys, ['key-1', 'key-1']);
      expect(generatedKeys, 1);
      expect(controller.hasPendingPayment(123), isTrue);

      final duplicate = await controller.registerInPersonPayment(123);
      expect(duplicate, isNull);
      expect(repository.inPersonKeys, isEmpty);

      controller.dispose();
    },
  );

  test(
    'loads and exposes payment status without treating pending as paid',
    () async {
      final repository = _FakePaymentRepository(failFirstOnlineRequest: false);
      final controller = PaymentController(
        repository,
        idempotencyKeyGenerator: () => 'new-key',
      );

      await controller.loadForOrder(123);
      final payment = await controller.requestOnlinePayment(123);

      expect(payment?.isPending, isTrue);
      expect(controller.paymentsFor(123).single.isPending, isTrue);
      expect(controller.isLoaded(123), isTrue);

      controller.dispose();
    },
  );
}

class _FakePaymentRepository implements PaymentRepository {
  _FakePaymentRepository({this.failFirstOnlineRequest = true});

  final bool failFirstOnlineRequest;
  final List<String> onlineKeys = [];
  final List<String> inPersonKeys = [];
  int _onlineCalls = 0;
  bool _created = false;

  static const _payment = Payment(
    id: 80,
    orderId: 123,
    amount: 12500,
    channel: 'EN_LINEA',
    status: 'PENDIENTE',
    reference: 'reference-80',
  );

  @override
  Future<List<Payment>> getForOrder(int orderId) async =>
      _created ? const [_payment] : const [];

  @override
  Future<Payment> requestOnlinePayment(
    int orderId,
    String idempotencyKey,
  ) async {
    onlineKeys.add(idempotencyKey);
    _onlineCalls++;
    if (failFirstOnlineRequest && _onlineCalls == 1) {
      throw const ApiException(message: 'Connection timed out.');
    }

    _created = true;
    return _payment;
  }

  @override
  Future<Payment> registerInPersonPayment(
    int orderId,
    String idempotencyKey,
  ) async {
    inPersonKeys.add(idempotencyKey);
    return _payment;
  }
}
