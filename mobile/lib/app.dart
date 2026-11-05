import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import 'core/network/api_client.dart';
import 'core/network/api_config.dart';
import 'routes/app_router.dart';

class HostalConnectApp extends StatelessWidget {
  const HostalConnectApp({super.key});

  static final GoRouter _router = createAppRouter();

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<FlutterSecureStorage>(
          create: (_) => const FlutterSecureStorage(),
        ),
        Provider<http.Client>(
          create: (_) => http.Client(),
          dispose: (_, client) => client.close(),
        ),
        Provider<ApiConfig>(create: (_) => ApiConfig.fromEnvironment()),
        Provider<ApiClient>(
          create: (context) {
            final secureStorage = context.read<FlutterSecureStorage>();

            return ApiClient(
              config: context.read<ApiConfig>(),
              httpClient: context.read<http.Client>(),
              accessTokenProvider: () =>
                  secureStorage.read(key: ApiClient.accessTokenStorageKey),
            );
          },
        ),
      ],
      child: MaterialApp.router(
        title: 'HostalConnect UNAB',
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF00529B)),
          useMaterial3: true,
        ),
        routerConfig: _router,
      ),
    );
  }
}
