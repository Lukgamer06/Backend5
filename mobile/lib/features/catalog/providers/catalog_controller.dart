import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../models/daily_menu.dart';
import '../models/product.dart';
import '../repositories/catalog_repository.dart';

class CatalogController extends ChangeNotifier {
  CatalogController(this._repository);

  static const _pageSize = 12;

  final CatalogRepository _repository;

  List<Product> _products = const [];
  DailyMenu? _dailyMenu;
  String? _catalogError;
  String? _menuError;
  bool _isLoadingProducts = false;
  bool _isLoadingMenu = false;
  int _currentPage = 1;
  int _lastPage = 1;
  int _totalProducts = 0;
  String _search = '';

  List<Product> get products => _products;
  DailyMenu? get dailyMenu => _dailyMenu;
  String? get catalogError => _catalogError;
  String? get menuError => _menuError;
  bool get isLoadingProducts => _isLoadingProducts;
  bool get isLoadingMenu => _isLoadingMenu;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get totalProducts => _totalProducts;
  bool get hasPreviousPage => _currentPage > 1;
  bool get hasNextPage => _currentPage < _lastPage;

  Future<void> load() async {
    await Future.wait([loadProducts(), loadDailyMenu()]);
  }

  Future<void> loadProducts({String? search, int? page}) async {
    if (_isLoadingProducts) {
      return;
    }

    if (search != null) {
      _search = search.trim();
    }
    final requestedPage = search != null ? 1 : page ?? _currentPage;
    _isLoadingProducts = true;
    _catalogError = null;
    notifyListeners();

    try {
      final result = await _repository.getProducts(
        page: requestedPage,
        perPage: _pageSize,
        search: _search,
      );
      _products = result.products;
      _currentPage = result.currentPage;
      _lastPage = result.lastPage;
      _totalProducts = result.total;
    } on ApiException catch (error) {
      _catalogError = error.message;
    } finally {
      _isLoadingProducts = false;
      notifyListeners();
    }
  }

  Future<void> loadDailyMenu() async {
    if (_isLoadingMenu) {
      return;
    }

    _isLoadingMenu = true;
    _menuError = null;
    notifyListeners();

    try {
      _dailyMenu = await _repository.getDailyMenu();
    } on ApiException catch (error) {
      if (error.statusCode == 404) {
        _dailyMenu = null;
      } else {
        _menuError = error.message;
      }
    } finally {
      _isLoadingMenu = false;
      notifyListeners();
    }
  }

  Future<void> goToPreviousPage() async {
    if (hasPreviousPage) {
      await loadProducts(page: _currentPage - 1);
    }
  }

  Future<void> goToNextPage() async {
    if (hasNextPage) {
      await loadProducts(page: _currentPage + 1);
    }
  }
}
