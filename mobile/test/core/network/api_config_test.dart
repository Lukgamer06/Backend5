import 'package:flutter_test/flutter_test.dart';
import 'package:hostalconnect_mobile/core/network/api_config.dart';

void main() {
  group('ApiConfig.fromEnvironment', () {
    test('defaults to the Android emulator API in development', () {
      final config = ApiConfig.fromEnvironment(environment: 'development');

      expect(config.environment, ApiEnvironment.development);
      expect(config.baseUrl, 'http://10.0.2.2:8080');
    });

    test('requires an explicit URL for staging and production', () {
      expect(
        () => ApiConfig.fromEnvironment(environment: 'staging'),
        throwsStateError,
      );
      expect(
        () => ApiConfig.fromEnvironment(environment: 'production'),
        throwsStateError,
      );
    });

    test('accepts remote HTTPS for staging', () {
      final config = ApiConfig.fromEnvironment(
        environment: 'staging',
        baseUrl: 'https://staging-api.hostalconnect.example.org',
      );

      expect(config.environment, ApiEnvironment.staging);
      expect(config.uriFor('me').scheme, 'https');
    });

    test('rejects HTTP and local hosts outside development', () {
      for (final url in [
        'http://staging-api.hostalconnect.org',
        'https://localhost',
        'https://127.0.0.1',
      ]) {
        expect(
          () => ApiConfig.fromEnvironment(environment: 'staging', baseUrl: url),
          throwsArgumentError,
          reason: url,
        );
      }
    });

    test('production requires HTTPS and a non-reserved domain', () {
      final config = ApiConfig.fromEnvironment(
        environment: 'production',
        baseUrl: 'https://api.hostalconnect.unab.edu.co',
      );

      expect(config.environment, ApiEnvironment.production);
      expect(config.uriFor('me').host, 'api.hostalconnect.unab.edu.co');

      for (final url in [
        'http://api.hostalconnect.unab.edu.co',
        'https://localhost',
        'https://192.0.2.10',
        'https://api.example.org',
        'https://api.hostalconnect.test',
      ]) {
        expect(
          () => ApiConfig.fromEnvironment(
            environment: 'production',
            baseUrl: url,
          ),
          throwsArgumentError,
          reason: url,
        );
      }
    });

    test('rejects unsupported environment names', () {
      expect(
        () => ApiConfig.fromEnvironment(environment: 'testing'),
        throwsArgumentError,
      );
    });
  });
}
