import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/storage/token_storage.dart';
import '../models/auth_user.dart';

abstract interface class AuthRepository {
  Future<AuthUser> login(String correo, String password);

  Future<AuthUser?> restoreSession();

  Future<void> logout();

  Future<void> clearToken();
}

class ApiAuthRepository implements AuthRepository {
  const ApiAuthRepository({
    required this._apiClient,
    required this._tokenStorage,
  });

  final ApiClient _apiClient;
  final TokenStorage _tokenStorage;

  @override
  Future<AuthUser> login(String correo, String password) async {
    final response = await _apiClient.post(
      'login',
      body: {'correo': correo, 'password': password},
    );
    final data = _object(response['data']);
    final token = data['access_token'];
    if (token is! String || token.trim().isEmpty) {
      throw const ApiException(
        message: 'El servidor no devolvió un token de acceso válido.',
      );
    }

    final user = AuthUser.fromJson(_object(data['usuario']));
    await _tokenStorage.writeToken(token);

    return user;
  }

  @override
  Future<AuthUser?> restoreSession() async {
    final token = await _tokenStorage.readToken();
    if (token == null || token.trim().isEmpty) {
      return null;
    }

    final response = await _apiClient.get('me');
    final data = _object(response['data']);

    return AuthUser.fromJson(_object(data['usuario']));
  }

  @override
  Future<void> logout() async {
    try {
      await _apiClient.post('logout');
    } finally {
      await _tokenStorage.deleteToken();
    }
  }

  @override
  Future<void> clearToken() {
    return _tokenStorage.deleteToken();
  }

  Map<String, dynamic> _object(Object? value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    throw const ApiException(
      message: 'El servidor devolvió una respuesta de autenticación no válida.',
    );
  }
}
