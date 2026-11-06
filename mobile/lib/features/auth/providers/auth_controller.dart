import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

import '../../../core/network/api_exception.dart';
import '../models/auth_user.dart';
import '../repositories/auth_repository.dart';

enum AuthStatus { loading, authenticated, unauthenticated }

class AuthController extends ChangeNotifier {
  AuthController(this._repository);

  final AuthRepository _repository;

  AuthStatus _status = AuthStatus.loading;
  AuthUser? _user;
  String? _message;
  bool _isLoading = true;

  AuthStatus get status => _status;
  AuthUser? get user => _user;
  String? get message => _message;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  Future<void> restoreSession() async {
    _status = AuthStatus.loading;
    _isLoading = true;

    try {
      _user = await _repository.restoreSession();
      _status = _user == null
          ? AuthStatus.unauthenticated
          : AuthStatus.authenticated;
    } on ApiException catch (error) {
      _user = null;
      _status = AuthStatus.unauthenticated;
      _message = error.statusCode == 401
          ? 'Tu sesión expiró. Inicia sesión nuevamente.'
          : error.message;
      if (error.statusCode == 401) {
        await _clearTokenAfterUnauthorized();
      }
    } on PlatformException {
      _user = null;
      _status = AuthStatus.unauthenticated;
      _message = 'No se pudo acceder al almacenamiento seguro del dispositivo.';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> login(String correo, String password) async {
    _isLoading = true;
    _message = null;
    notifyListeners();

    try {
      _user = await _repository.login(correo, password);
      _status = AuthStatus.authenticated;
    } on ApiException catch (error) {
      _user = null;
      _status = AuthStatus.unauthenticated;
      _message = _validationMessage(error);
    } on PlatformException {
      _user = null;
      _status = AuthStatus.unauthenticated;
      _message = 'No se pudo acceder al almacenamiento seguro del dispositivo.';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    _isLoading = true;
    _message = null;
    notifyListeners();

    try {
      await _repository.logout();
    } on ApiException catch (error) {
      _message = error.message;
    } on PlatformException {
      _message = 'No se pudo borrar el token del almacenamiento seguro.';
    } finally {
      _user = null;
      _status = AuthStatus.unauthenticated;
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> handleUnauthorized() async {
    _user = null;
    _status = AuthStatus.unauthenticated;
    _message = 'Tu sesión expiró. Inicia sesión nuevamente.';
    _isLoading = false;
    notifyListeners();

    try {
      await _repository.clearToken();
    } on PlatformException {
      _message = 'No se pudo borrar el token del almacenamiento seguro.';
      notifyListeners();
    }
  }

  Future<void> _clearTokenAfterUnauthorized() async {
    try {
      await _repository.clearToken();
    } on PlatformException {
      _message = 'No se pudo borrar el token del almacenamiento seguro.';
    }
  }

  String _validationMessage(ApiException error) {
    for (final messages in error.validationErrors.values) {
      if (messages.isNotEmpty) {
        return messages.first;
      }
    }

    return error.message;
  }
}
