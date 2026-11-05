import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/providers/auth_controller.dart';
import '../features/auth/screens/home_screen.dart';
import '../features/auth/screens/login_screen.dart';

GoRouter createAppRouter(AuthController authController) {
  return GoRouter(
    initialLocation: '/',
    refreshListenable: authController,
    redirect: (context, state) {
      final path = state.uri.path;

      return switch (authController.status) {
        AuthStatus.loading => path == '/loading' ? null : '/loading',
        AuthStatus.unauthenticated => path == '/login' ? null : '/login',
        AuthStatus.authenticated =>
          path == '/login' || path == '/loading' ? '/' : null,
      };
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const HomeScreen()),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/loading',
        builder: (context, state) =>
            const Scaffold(body: Center(child: CircularProgressIndicator())),
      ),
    ],
  );
}
