import 'dart:math';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../models/payment.dart';
import '../repositories/payment_repository.dart';

typedef IdempotencyKeyGenerator = String Function();

class PaymentController extends ChangeNotifier {
  PaymentController(
    this._repository, {
    IdempotencyKeyGenerator? idempotencyKeyGenerator,
  }) : _idempotencyKeyGenerator =
           idempotencyKeyGenerator ?? _generateIdempotencyKey;

  final PaymentRepository _repository;
  final IdempotencyKeyGenerator _idempotencyKeyGenerator;
  final Map<int, List<Payment>> _paymentsByOrder = {};
  final Set<int> _ordersLoaded = {};
  final Set<int> _ordersBeingLoaded = {};
  final Set<int> _ordersBeingSubmitted = {};
  final Map<String, String> _idempotencyKeys = {};
  final Map<int, String> _errorsByOrder = {};

  List<Payment> paymentsFor(int orderId) =>
      List.unmodifiable(_paymentsByOrder[orderId] ?? const []);
  bool isLoading(int orderId) => _ordersBeingLoaded.contains(orderId);
  bool isLoaded(int orderId) => _ordersLoaded.contains(orderId);
  bool isSubmitting(int orderId) => _ordersBeingSubmitted.contains(orderId);
  String? errorFor(int orderId) => _errorsByOrder[orderId];
  bool hasPendingPayment(int orderId) =>
      paymentsFor(orderId).any((payment) => payment.isPending);

  Future<void> loadForOrder(int orderId) async {
    if (!_ordersBeingLoaded.add(orderId)) {
      return;
    }
    _errorsByOrder.remove(orderId);
    notifyListeners();

    try {
      _paymentsByOrder[orderId] = await _repository.getForOrder(orderId);
      _ordersLoaded.add(orderId);
    } on ApiException catch (error) {
      _errorsByOrder[orderId] = error.message;
    } finally {
      _ordersBeingLoaded.remove(orderId);
      notifyListeners();
    }
  }

  Future<Payment?> requestOnlinePayment(int orderId) {
    return _createPayment(orderId, 'EN_LINEA', (key) {
      return _repository.requestOnlinePayment(orderId, key);
    });
  }

  Future<Payment?> registerInPersonPayment(int orderId) {
    return _createPayment(orderId, 'PRESENCIAL', (key) {
      return _repository.registerInPersonPayment(orderId, key);
    });
  }

  Future<Payment?> _createPayment(
    int orderId,
    String channel,
    Future<Payment> Function(String key) create,
  ) async {
    if (_ordersBeingSubmitted.contains(orderId) || hasPendingPayment(orderId)) {
      return null;
    }

    final operation = '$orderId:$channel';
    final key = _idempotencyKeys.putIfAbsent(
      operation,
      _idempotencyKeyGenerator,
    );
    _ordersBeingSubmitted.add(orderId);
    _errorsByOrder.remove(orderId);
    notifyListeners();

    try {
      final payment = await create(key);
      _idempotencyKeys.remove(operation);
      await loadForOrder(orderId);

      return payment;
    } on ApiException catch (error) {
      _errorsByOrder[orderId] = error.message;

      return null;
    } finally {
      _ordersBeingSubmitted.remove(orderId);
      notifyListeners();
    }
  }
}

String _generateIdempotencyKey() {
  final random = Random.secure();
  return List.generate(32, (_) => random.nextInt(16).toRadixString(16)).join();
}
