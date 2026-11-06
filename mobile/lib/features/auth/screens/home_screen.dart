import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../catalog/models/product.dart';
import '../../catalog/providers/catalog_controller.dart';
import '../providers/auth_controller.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authController = context.watch<AuthController>();
    final catalogController = context.watch<CatalogController>();
    final user = authController.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Catálogo'),
        actions: [
          TextButton(
            onPressed: authController.isLoading ? null : authController.logout,
            child: const Text('Cerrar sesión'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: catalogController.load,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (user != null)
                      Text(
                        'Hola, ${user.nombres}',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                    const SizedBox(height: 16),
                    _DailyMenuSection(controller: catalogController),
                    const SizedBox(height: 20),
                    Text(
                      'Productos',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    TextField(
                      key: const Key('product-search'),
                      controller: _searchController,
                      textInputAction: TextInputAction.search,
                      onSubmitted: (value) =>
                          catalogController.loadProducts(search: value),
                      decoration: InputDecoration(
                        labelText: 'Buscar productos',
                        prefixIcon: const Icon(Icons.search),
                        suffixIcon: IconButton(
                          tooltip: 'Limpiar búsqueda',
                          onPressed: () {
                            _searchController.clear();
                            catalogController.loadProducts(search: '');
                          },
                          icon: const Icon(Icons.clear),
                        ),
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 8),
                    if (catalogController.catalogError case final error?)
                      _ErrorMessage(
                        message: error,
                        onRetry: catalogController.loadProducts,
                      ),
                  ],
                ),
              ),
            ),
            if (catalogController.isLoadingProducts &&
                catalogController.products.isEmpty)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: Center(child: CircularProgressIndicator()),
              )
            else if (catalogController.products.isEmpty &&
                catalogController.catalogError == null)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: Center(child: Text('No hay productos para mostrar.')),
              )
            else if (catalogController.products.isEmpty)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: SizedBox.shrink(),
              )
            else
              SliverList.builder(
                itemCount: catalogController.products.length,
                itemBuilder: (context, index) =>
                    _ProductTile(product: catalogController.products[index]),
              ),
            if (catalogController.totalProducts > 0)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      IconButton(
                        tooltip: 'Página anterior',
                        onPressed:
                            catalogController.isLoadingProducts ||
                                !catalogController.hasPreviousPage
                            ? null
                            : catalogController.goToPreviousPage,
                        icon: const Icon(Icons.chevron_left),
                      ),
                      Text(
                        'Página ${catalogController.currentPage} de ${catalogController.lastPage}',
                      ),
                      IconButton(
                        tooltip: 'Página siguiente',
                        onPressed:
                            catalogController.isLoadingProducts ||
                                !catalogController.hasNextPage
                            ? null
                            : catalogController.goToNextPage,
                        icon: const Icon(Icons.chevron_right),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _DailyMenuSection extends StatelessWidget {
  const _DailyMenuSection({required this.controller});

  final CatalogController controller;

  @override
  Widget build(BuildContext context) {
    final menu = controller.dailyMenu;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Menú del día', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            if (controller.isLoadingMenu)
              const LinearProgressIndicator()
            else if (controller.menuError case final error?)
              _ErrorMessage(message: error, onRetry: controller.loadDailyMenu)
            else if (menu == null)
              const Text('El menú de hoy todavía no está publicado.')
            else ...[
              Text(
                '${menu.date.day.toString().padLeft(2, '0')}/${menu.date.month.toString().padLeft(2, '0')}/${menu.date.year}',
              ),
              const SizedBox(height: 8),
              for (final product in menu.products)
                Text('${product.name} · ${_formatPrice(product.price)}'),
            ],
          ],
        ),
      ),
    );
  }
}

class _ProductTile extends StatelessWidget {
  const _ProductTile({required this.product});

  final Product product;

  @override
  Widget build(BuildContext context) {
    final details = [
      if (product.description?.isNotEmpty ?? false) product.description!,
      if (!product.available) 'No disponible',
    ];

    return ListTile(
      title: Text(product.name),
      subtitle: details.isEmpty ? null : Text(details.join(' · ')),
      trailing: Text(_formatPrice(product.price)),
    );
  }
}

class _ErrorMessage extends StatelessWidget {
  const _ErrorMessage({required this.message, required this.onRetry});

  final String message;
  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            message,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ),
        IconButton(
          tooltip: 'Reintentar',
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
        ),
      ],
    );
  }
}

String _formatPrice(double price) {
  return '\$${price.toStringAsFixed(2)}';
}
