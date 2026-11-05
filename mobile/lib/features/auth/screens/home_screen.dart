import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_controller.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authController = context.watch<AuthController>();
    final user = authController.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('HostalConnect UNAB'),
        actions: [
          TextButton(
            onPressed: authController.isLoading ? null : authController.logout,
            child: const Text('Cerrar sesión'),
          ),
        ],
      ),
      body: Center(
        child: user == null
            ? const CircularProgressIndicator()
            : Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Bienvenido, ${user.nombreCompleto}',
                      style: Theme.of(context).textTheme.headlineSmall,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    Text(user.correo),
                    const SizedBox(height: 4),
                    Text('Rol: ${user.rol}'),
                    if (authController.message case final message?) ...[
                      const SizedBox(height: 16),
                      Text(
                        message,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.error,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ],
                ),
              ),
      ),
    );
  }
}
