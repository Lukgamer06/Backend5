import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_client.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';
import 'package:hostalconnect_mobile/core/network/api_exception.dart';
import 'package:hostalconnect_mobile/core/storage/token_storage.dart';
import 'package:hostalconnect_mobile/features/auth/repositories/auth_repository.dart';
import 'package:hostalconnect_mobile/features/catalog/repositories/catalog_repository.dart';
import 'package:hostalconnect_mobile/features/orders/repositories/order_repository.dart';
import 'package:hostalconnect_mobile/features/reservations/repositories/reservation_repository.dart';
import 'package:http/http.dart' as http;

const _baseUrl = String.fromEnvironment('LIVE_API_BASE_URL');
const _environment = String.fromEnvironment('APP_ENV', defaultValue: 'staging');
const _email = String.fromEnvironment('LIVE_API_EMAIL');
const _password = String.fromEnvironment('LIVE_API_PASSWORD');

const _skipReason =
    _environment != 'staging' ||
        _baseUrl == '' ||
        _email == '' ||
        _password == ''
    ? 'Configura staging y .live-api-test.json con una API remota y credenciales de cliente de pruebas.'
    : null;

void main() {
  test(
    'live API smoke: auth, catalog, orders, reservations and safe error contracts',
    () async {
      final config = ApiConfig.fromEnvironment(
        environment: _environment,
        baseUrl: _baseUrl,
      );
      final httpClient = http.Client();
      final tokenStorage = _MemoryTokenStorage();
      final apiClient = ApiClient(
        config: config,
        httpClient: httpClient,
        accessTokenProvider: tokenStorage.readToken,
      );
      final authRepository = ApiAuthRepository(
        apiClient: apiClient,
        tokenStorage: tokenStorage,
      );
      final catalogRepository = ApiCatalogRepository(apiClient);
      final orderRepository = ApiOrderRepository(apiClient);
      final reservationRepository = ApiReservationRepository(apiClient);
      var authenticated = false;

      try {
        await _expectStatus(
          () => apiClient.get('me'),
          401,
          'Sin token, GET /me',
        );

        final customer = await authRepository.login(_email, _password);
        authenticated = true;
        expect(customer.rol, 'CLIENTE', reason: 'La cuenta de smoke test');

        expect(await authRepository.restoreSession(), isNotNull);
        final productPage = await catalogRepository.getProducts(
          page: 1,
          perPage: 5,
        );
        expect(productPage.currentPage, 1);
        expect(productPage.products.length, lessThanOrEqualTo(5));
        await catalogRepository.getMenus(page: 1, perPage: 5);
        await orderRepository.getOrders(page: 1);
        await reservationRepository.getReservations(page: 1);

        final tomorrow = DateTime.now().add(const Duration(days: 1));
        final startsAt = DateTime(
          tomorrow.year,
          tomorrow.month,
          tomorrow.day,
          12,
        );
        await reservationRepository.getAvailableTables(
          startsAt: startsAt,
          endsAt: startsAt.add(const Duration(hours: 1)),
          guestCount: 1,
        );

        await _expectStatus(
          () => apiClient.get('incidencias'),
          403,
          'Un cliente no puede consultar incidencias de personal',
        );
        await _expectStatus(
          () => apiClient.get('productos/no-es-un-id'),
          404,
          'Identificador de producto inexistente',
        );
        final validationError = await _expectStatus(
          () => apiClient.post('reservas', body: const {}),
          422,
          'Reserva sin campos requeridos',
        );
        expect(
          validationError.validationErrors,
          isNotEmpty,
          reason: '422 debe incluir meta.errors parseable.',
        );
      } finally {
        try {
          if (authenticated) {
            await authRepository.logout();
          }
        } finally {
          httpClient.close();
        }
      }
    },
    skip: _skipReason,
    timeout: const Timeout(Duration(minutes: 2)),
  );
}

Future<ApiException> _expectStatus(
  Future<Object?> Function() request,
  int expectedStatus,
  String reason,
) async {
  try {
    await request();
  } on ApiException catch (error) {
    expect(error.statusCode, expectedStatus, reason: reason);
    return error;
  }

  fail('$reason debe responder HTTP $expectedStatus.');
}

class _MemoryTokenStorage implements TokenStorage {
  String? _token;

  @override
  Future<String?> readToken() async => _token;

  @override
  Future<void> writeToken(String token) async {
    _token = token;
  }

  @override
  Future<void> deleteToken() async {
    _token = null;
  }
}
