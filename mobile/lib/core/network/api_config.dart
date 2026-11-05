class ApiConfig {
  const ApiConfig({
    required this.baseUrl,
    this.apiPrefix = '/api/v1',
    this.requestTimeout = const Duration(seconds: 20),
  });

  factory ApiConfig.fromEnvironment({String? baseUrl}) {
    return ApiConfig(
      baseUrl:
          baseUrl ??
          const String.fromEnvironment(
            'API_BASE_URL',
            defaultValue: 'http://10.0.2.2:8080',
          ),
    );
  }

  final String baseUrl;
  final String apiPrefix;
  final Duration requestTimeout;

  Uri uriFor(String path, {Map<String, String>? queryParameters}) {
    final origin = Uri.parse(baseUrl);
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
