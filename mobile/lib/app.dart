import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import 'core/network/api_client.dart';
import 'core/network/api_config.dart';
import 'core/storage/token_storage.dart';
import 'features/auth/providers/auth_controller.dart';
import 'features/auth/repositories/auth_repository.dart';
import 'features/catalog/providers/catalog_controller.dart';
import 'features/catalog/repositories/catalog_repository.dart';
import 'features/orders/providers/order_controller.dart';
import 'features/orders/repositories/order_repository.dart';
import 'features/payments/providers/payment_controller.dart';
import 'features/payments/repositories/payment_repository.dart';
import 'routes/app_router.dart';

class HostalConnectApp extends StatelessWidget {
  const HostalConnectApp({super.key});

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
        Provider<TokenStorage>(
          create: (context) =>
              SecureTokenStorage(context.read<FlutterSecureStorage>()),
        ),
        Provider<ApiClient>(
          create: (context) {
            final tokenStorage = context.read<TokenStorage>();

            return ApiClient(
              config: context.read<ApiConfig>(),
              httpClient: context.read<http.Client>(),
              accessTokenProvider: tokenStorage.readToken,
              unauthorizedHandler: () =>
                  context.read<AuthController>().handleUnauthorized(),
            );
          },
        ),
        Provider<AuthRepository>(
          create: (context) => ApiAuthRepository(
            apiClient: context.read<ApiClient>(),
            tokenStorage: context.read<TokenStorage>(),
          ),
        ),
        Provider<CatalogRepository>(
          create: (context) => ApiCatalogRepository(context.read<ApiClient>()),
        ),
        Provider<OrderRepository>(
          create: (context) => ApiOrderRepository(context.read<ApiClient>()),
        ),
        Provider<PaymentRepository>(
          create: (context) => ApiPaymentRepository(context.read<ApiClient>()),
        ),
        ChangeNotifierProvider<AuthController>(
          create: (context) {
            final controller = AuthController(context.read<AuthRepository>());
            unawaited(controller.restoreSession());

            return controller;
          },
        ),
        ChangeNotifierProvider<CatalogController>(
          create: (context) {
            final controller = CatalogController(
              context.read<CatalogRepository>(),
            );
            unawaited(controller.load());

            return controller;
          },
        ),
        ChangeNotifierProvider<OrderController>(
          create: (context) => OrderController(context.read<OrderRepository>()),
        ),
        ChangeNotifierProvider<PaymentController>(
          create: (context) =>
              PaymentController(context.read<PaymentRepository>()),
        ),
        Provider<GoRouter>(
          create: (context) => createAppRouter(context.read<AuthController>()),
          dispose: (_, router) => router.dispose(),
        ),
      ],
      child: Builder(
        builder: (context) => MaterialApp.router(
          title: 'HostalConnect UNAB',
          theme: ThemeData(
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xFF00529B),
            ),
            useMaterial3: true,
          ),
          routerConfig: context.watch<GoRouter>(),
        ),
      ),
    );
  }
}
