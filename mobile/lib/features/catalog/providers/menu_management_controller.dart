import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../models/daily_menu.dart';
import '../models/menu_page.dart';
import '../models/product.dart';
import '../repositories/catalog_repository.dart';

class MenuManagementController extends ChangeNotifier {
  MenuManagementController(this._repository);

  final CatalogRepository _repository;

  List<DailyMenu> _menus = const [];
  List<Product> _products = const [];
  String? _error;
  String? _actionError;
  String? _status;
  bool _isLoadingMenus = false;
  bool _isLoadingProducts = false;
  bool _isSubmitting = false;
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  int _loadGeneration = 0;

  List<DailyMenu> get menus => _menus;
  List<Product> get products => _products;
  String? get error => _error;
  String? get actionError => _actionError;
  String? get status => _status;
  bool get isLoadingMenus => _isLoadingMenus;
  bool get isLoadingProducts => _isLoadingProducts;
  bool get isSubmitting => _isSubmitting;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get total => _total;

  Future<void> loadMenus({int? page, String? status}) async {
    if (status != null) {
      _status = status.isEmpty ? null : status;
      _currentPage = 1;
    }

    final generation = ++_loadGeneration;
    _isLoadingMenus = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _repository.getMenus(
        page: page ?? _currentPage,
        perPage: 10,
        status: _status,
      );
      if (generation == _loadGeneration) {
        _applyMenuPage(result);
      }
    } on ApiException catch (error) {
      if (generation == _loadGeneration) {
        _error = error.message;
      }
    } finally {
      if (generation == _loadGeneration) {
        _isLoadingMenus = false;
        notifyListeners();
      }
    }
  }

  Future<void> loadProducts({String? search}) async {
    _isLoadingProducts = true;
    _actionError = null;
    notifyListeners();

    try {
      _products = await _repository.getMenuProducts(search: search);
    } on ApiException catch (error) {
      _actionError = error.message;
    } finally {
      _isLoadingProducts = false;
      notifyListeners();
    }
  }

  Future<DailyMenu?> saveMenu({
    int? menuId,
    required DateTime date,
    required List<int> productIds,
  }) async {
    if (_isSubmitting) {
      return null;
    }
    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      final menu = menuId == null
          ? await _repository.createMenu(date: date, productIds: productIds)
          : await _repository.updateMenu(
              menuId: menuId,
              date: date,
              productIds: productIds,
            );
      await loadMenus(page: 1);

      return menu;
    } on ApiException catch (error) {
      _actionError = error.message;

      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<DailyMenu?> publishMenu(int menuId) async {
    if (_isSubmitting) {
      return null;
    }
    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      final menu = await _repository.publishMenu(menuId);
      await loadMenus(page: _currentPage);

      return menu;
    } on ApiException catch (error) {
      _actionError = error.message;

      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> goToPreviousPage() async {
    if (_currentPage > 1) {
      await loadMenus(page: _currentPage - 1);
    }
  }

  Future<void> goToNextPage() async {
    if (_currentPage < _lastPage) {
      await loadMenus(page: _currentPage + 1);
    }
  }

  void _applyMenuPage(MenuPage page) {
    _menus = page.menus;
    _currentPage = page.currentPage;
    _lastPage = page.lastPage;
    _total = page.total;
  }
}
