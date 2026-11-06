enum ApiEnvironment { development, staging, production }

class ApiConfig {
  const ApiConfig({
    required this.baseUrl,
    this.environment = ApiEnvironment.development,
    this.apiPrefix = '/api/v1',
    this.requestTimeout = const Duration(seconds: 20),
  });

  factory ApiConfig.fromEnvironment({String? baseUrl, String? environment}) {
    final environmentName =
        environment ??
        const String.fromEnvironment('APP_ENV', defaultValue: 'development');
    final apiEnvironment = switch (environmentName) {
      'development' => ApiEnvironment.development,
      'staging' => ApiEnvironment.staging,
      'production' => ApiEnvironment.production,
      _ => throw ArgumentError.value(
        environmentName,
        'environment',
        'Debe ser development, staging o production.',
      ),
    };
    final configuredBaseUrl =
        baseUrl ??
        const String.fromEnvironment('API_BASE_URL', defaultValue: '');
    final resolvedBaseUrl = configuredBaseUrl.isEmpty
        ? switch (apiEnvironment) {
            ApiEnvironment.development => 'http://10.0.2.2:8080',
            ApiEnvironment.staging ||
            ApiEnvironment.production => throw StateError(
              'API_BASE_URL es obligatorio para staging y production.',
            ),
          }
        : configuredBaseUrl;

    _validateBaseUrl(resolvedBaseUrl, apiEnvironment);

    return ApiConfig(baseUrl: resolvedBaseUrl, environment: apiEnvironment);
  }

  final String baseUrl;
  final ApiEnvironment environment;
  final String apiPrefix;
  final Duration requestTimeout;

  Uri uriFor(String path, {Map<String, String>? queryParameters}) {
    final origin = _validateBaseUrl(baseUrl, environment);
    final normalizedPrefix = apiPrefix
        .split('/')
        .where((segment) => segment.isNotEmpty)
        .join('/');
    final normalizedPath = path
        .split('/')
        .where((segment) => segment.isNotEmpty)
        .join('/');

    if (normalizedPrefix.isEmpty ||
        normalizedPath.isEmpty ||
        normalizedPath.split('/').contains('..') ||
        normalizedPath.contains('?') ||
        normalizedPath.contains('#')) {
      throw ArgumentError.value(path, 'path', 'La ruta API no es válida.');
    }

    return Uri(
      scheme: origin.scheme,
      host: origin.host,
      port: origin.hasPort ? origin.port : null,
      path: '/$normalizedPrefix/$normalizedPath',
      queryParameters: queryParameters,
    );
  }
}

Uri _validateBaseUrl(String baseUrl, ApiEnvironment environment) {
  final Uri origin;
  try {
    origin = Uri.parse(baseUrl);
  } on FormatException {
    throw ArgumentError.value(baseUrl, 'baseUrl', 'La URL base no es válida.');
  }

  if (!origin.hasScheme ||
      !origin.hasAuthority ||
      !['http', 'https'].contains(origin.scheme) ||
      origin.userInfo.isNotEmpty ||
      (origin.path.isNotEmpty && origin.path != '/') ||
      origin.hasQuery ||
      origin.hasFragment) {
    throw ArgumentError.value(
      baseUrl,
      'baseUrl',
      'Debe ser una URL HTTP(S) de origen, sin ruta ni credenciales.',
    );
  }

  if (environment != ApiEnvironment.development &&
      (origin.scheme != 'https' || _isLocalHost(origin.host))) {
    throw ArgumentError.value(
      baseUrl,
      'baseUrl',
      'Staging y production requieren un host remoto con HTTPS.',
    );
  }

  if (environment == ApiEnvironment.production &&
      (_isIpAddress(origin.host) || _isReservedHost(origin.host))) {
    throw ArgumentError.value(
      baseUrl,
      'baseUrl',
      'Production requiere un dominio no reservado, no una IP ni un host de ejemplo.',
    );
  }

  return origin;
}

bool _isLocalHost(String host) {
  final normalizedHost = host.toLowerCase();

  return normalizedHost == 'localhost' ||
      normalizedHost.endsWith('.localhost') ||
      normalizedHost.endsWith('.local') ||
      normalizedHost == '127.0.0.1' ||
      normalizedHost == '::1' ||
      normalizedHost == '0.0.0.0';
}

bool _isIpAddress(String host) {
  return host.contains(':') ||
      RegExp(r'^\d{1,3}(\.\d{1,3}){3}$').hasMatch(host);
}

bool _isReservedHost(String host) {
  final normalizedHost = host.toLowerCase();
  const reservedDomains = [
    'example',
    'example.com',
    'example.net',
    'example.org',
    'invalid',
    'test',
  ];

  return !normalizedHost.contains('.') ||
      normalizedHost.endsWith('.localhost') ||
      normalizedHost.endsWith('.local') ||
      reservedDomains.any(
        (domain) =>
            normalizedHost == domain || normalizedHost.endsWith('.$domain'),
      );
}
