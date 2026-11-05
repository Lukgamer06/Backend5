import 'package:flutter_secure_storage/flutter_secure_storage.dart';

abstract interface class TokenStorage {
  Future<String?> readToken();

  Future<void> writeToken(String token);

  Future<void> deleteToken();
}

class SecureTokenStorage implements TokenStorage {
  const SecureTokenStorage(this._storage);

  static const accessTokenKey = 'sanctum_access_token';

  final FlutterSecureStorage _storage;

  @override
  Future<String?> readToken() {
    return _storage.read(key: accessTokenKey);
  }

  @override
  Future<void> writeToken(String token) {
    return _storage.write(key: accessTokenKey, value: token);
  }

  @override
  Future<void> deleteToken() {
    return _storage.delete(key: accessTokenKey);
  }
}
