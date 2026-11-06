import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../catalog/models/product.dart';
import '../models/order.dart';
import '../repositories/order_repository.dart';

class OrderController extends ChangeNotifier {
  OrderController(this._repository);

  final OrderRepository _repository;
  final Map<int, _CartLine> _cart = {};

  List<CustomerOrder> _orders = const [];
  String? _error;
  String? _statusFilter;
  bool _isLoadingOrders = false;
  bool _isSubmitting = false;
  int _currentPage = 1;
  int _lastPage = 1;
  int _totalOrders = 0;
  int _loadGeneration = 0;

  List<CustomerOrder> get orders => _orders;
  String? get error => _error;
  String? get statusFilter => _statusFilter;
  bool get isLoadingOrders => _isLoadingOrders;
  bool get isSubmitting => _isSubmitting;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get totalOrders => _totalOrders;
  int get cartItemCount =>
      _cart.values.fold(0, (total, line) => total + line.quantity);
  List<CartItem> get cartItems => [
    for (final entry in _cart.entries)
      CartItem(product: entry.value.product, quantity: entry.value.quantity),
  ];

  Future<void> loadOrders({int? page, String? status}) async {
    if (status != null) {
      _statusFilter = status.isEmpty ? null : status;
      _currentPage = 1;
    }
    final requestedPage = page ?? _currentPage;
    final requestedStatus = _statusFilter;
    final generation = ++_loadGeneration;
    _isLoadingOrders = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _repository.getOrders(
        page: requestedPage,
        status: requestedStatus,
      );
      if (generation == _loadGeneration) {
        _orders = result.orders;
        _currentPage = result.currentPage;
        _lastPage = result.lastPage;
        _totalOrders = result.total;
      }
    } on ApiException catch (error) {
      if (generation == _loadGeneration) {
        _error = error.message;
      }
    } finally {
      if (generation == _loadGeneration) {
        _isLoadingOrders = false;
        notifyListeners();
      }
    }
  }

  void addProduct(Product product) {
    if (!product.available) {
      return;
    }
    final existing = _cart[product.id];
    _cart[product.id] = _CartLine(
      product: product,
      quantity: (existing?.quantity ?? 0) + 1,
    );
    notifyListeners();
  }

  void decreaseProduct(int productId) {
    final existing = _cart[productId];
    if (existing == null) {
      return;
    }
    if (existing.quantity == 1) {
      _cart.remove(productId);
    } else {
      _cart[productId] = _CartLine(
        product: existing.product,
        quantity: existing.quantity - 1,
      );
    }
    notifyListeners();
  }

  Future<CustomerOrder?> submitOrder({
    required bool scheduled,
    DateTime? scheduledAt,
  }) async {
    if (_isSubmitting || _cart.isEmpty) {
      return null;
    }
    if (scheduled &&
        (scheduledAt == null || !scheduledAt.isAfter(DateTime.now()))) {
      _error = 'Selecciona una fecha y hora futura.';
      notifyListeners();

      return null;
    }

    _isSubmitting = true;
    _error = null;
    notifyListeners();

    try {
      final order = await _repository.createOrder(
        attentionType: scheduled ? 'PROGRAMADO' : 'INMEDIATO',
        serviceMode: 'SIN_MESA',
        scheduledAt: scheduled ? scheduledAt : null,
        lines: [
          for (final line in _cart.values)
            {'producto_id': line.product.id, 'cantidad': line.quantity},
        ],
      );
      _cart.clear();
      await loadOrders();

      return order;
    } on ApiException catch (error) {
      _error = _firstValidationMessage(error) ?? error.message;

      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> cancelOrder(CustomerOrder order) async {
    if (!order.canRequestCancellation) {
      return;
    }
    _isSubmitting = true;
    _error = null;
    notifyListeners();

    try {
      await _repository.cancelOrder(order.id);
      await loadOrders();
    } on ApiException catch (error) {
      _error = error.message;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> goToPreviousPage() async {
    if (_currentPage > 1) {
      await loadOrders(page: _currentPage - 1);
    }
  }

  Future<void> goToNextPage() async {
    if (_currentPage < _lastPage) {
      await loadOrders(page: _currentPage + 1);
    }
  }

  String? _firstValidationMessage(ApiException error) {
    for (final messages in error.validationErrors.values) {
      if (messages.isNotEmpty) {
        return messages.first;
      }
    }

    return null;
  }
}

class CartItem {
  const CartItem({required this.product, required this.quantity});

  final Product product;
  final int quantity;
}

class _CartLine {
  const _CartLine({required this.product, required this.quantity});

  final Product product;
  final int quantity;
}
