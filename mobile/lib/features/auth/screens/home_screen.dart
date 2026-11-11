import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../catalog/models/product.dart';
import '../../catalog/providers/catalog_controller.dart';
import '../../orders/providers/order_controller.dart';
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
    final orderController = context.watch<OrderController>();
    final user = authController.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Catálogo'),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Abrir secciones',
            onSelected: (route) => context.push(route),
            itemBuilder: (context) => [
              if (user?.rol == 'CLIENTE')
                const PopupMenuItem(
                  key: Key('open-reservations'),
                  value: '/reservas',
                  child: Text('Mis reservas'),
                ),
              if (user?.rol == 'ADMINISTRADOR' || user?.rol == 'OPERADOR')
                const PopupMenuItem(
                  key: Key('open-incidents'),
                  value: '/incidencias',
                  child: Text('Incidencias'),
                ),
              if (user?.rol == 'ADMINISTRADOR' || user?.rol == 'OPERADOR')
                const PopupMenuItem(
                  key: Key('manage-daily-menu'),
                  value: '/menus/administrar',
                  child: Text('Administrar menú del día'),
                ),
              PopupMenuItem(
                value: '/pedidos',
                child: Text(
                  user?.rol == 'ADMINISTRADOR' || user?.rol == 'OPERADOR'
                      ? 'Gestión de pedidos'
                      : 'Mis pedidos',
                ),
              ),
            ],
            icon: const Icon(Icons.grid_view_rounded),
          ),
          TextButton(
            onPressed: authController.isLoading ? null : authController.logout,
            child: const Text('Cerrar sesión'),
          ),
        ],
      ),
      floatingActionButton:
          user?.rol != 'CLIENTE' || orderController.cartItemCount == 0
          ? null
          : FloatingActionButton.extended(
              key: const Key('open-cart'),
              onPressed: () => context.push('/pedidos/nuevo'),
              icon: const Icon(Icons.shopping_cart_checkout),
              label: Text('Carrito (${orderController.cartItemCount})'),
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
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [
                              Theme.of(context).colorScheme.primary,
                              Theme.of(context).colorScheme.primary.withValues(
                                alpha: 0.84,
                              ),
                            ],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(22),
                        ),
                        child: Row(
                          children: [
                            CircleAvatar(
                              radius: 25,
                              backgroundColor: Colors.white.withValues(
                                alpha: 0.16,
                              ),
                              child: Text(
                                user.nombres.isEmpty
                                    ? 'H'
                                    : user.nombres.substring(0, 1).toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 20,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Hola, ${user.nombres}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleLarge
                                        ?.copyWith(color: Colors.white),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    user.rol == 'CLIENTE'
                                        ? '¿Qué te gustaría disfrutar hoy?'
                                        : 'Panel de operación del hostal',
                                    style: TextStyle(
                                      color: Colors.white.withValues(
                                        alpha: 0.82,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 20),
                    _DailyMenuSection(controller: catalogController),
                    const SizedBox(height: 24),
                    Text(
                      'Explora el catálogo',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Encuentra algo para disfrutar.',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 14),
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
                itemBuilder: (context, index) => _ProductTile(
                  product: catalogController.products[index],
                  canOrder: user?.rol == 'CLIENTE',
                  onAdd: () {
                    orderController.addProduct(
                      catalogController.products[index],
                    );
                    ScaffoldMessenger.of(context)
                      ..hideCurrentSnackBar()
                      ..showSnackBar(
                        SnackBar(
                          content: Text(
                            '${catalogController.products[index].name} agregado al carrito.',
                          ),
                          duration: const Duration(seconds: 1),
                        ),
                      );
                  },
                ),
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
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 21,
                  backgroundColor: Theme.of(
                    context,
                  ).colorScheme.primary.withValues(alpha: 0.08),
                  child: Icon(
                    Icons.restaurant_menu_rounded,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Menú del día',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      Text(
                        'Una selección preparada para hoy',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            if (controller.isLoadingMenu)
              const LinearProgressIndicator()
            else if (controller.menuError case final error?)
              _ErrorMessage(message: error, onRetry: controller.loadDailyMenu)
            else if (menu == null)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Theme.of(context).scaffoldBackgroundColor,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Text('El menú de hoy todavía no está publicado.'),
              )
            else ...[
              for (final product in menu.products)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 5),
                  child: Row(
                    children: [
                      Icon(
                        Icons.check_circle_rounded,
                        size: 18,
                        color: Theme.of(context).colorScheme.secondary,
                      ),
                      const SizedBox(width: 10),
                      Expanded(child: Text(product.name)),
                      const SizedBox(width: 8),
                      Text(
                        _formatPrice(product.price),
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                    ],
                  ),
                ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ProductTile extends StatelessWidget {
  const _ProductTile({
    required this.product,
    required this.canOrder,
    required this.onAdd,
  });

  final Product product;
  final bool canOrder;
  final VoidCallback onAdd;

  @override
  Widget build(BuildContext context) {
    final details = [
      if (product.description?.isNotEmpty ?? false) product.description!,
      if (!product.available) 'No disponible',
    ];

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
      clipBehavior: Clip.antiAlias,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
        leading: CircleAvatar(
          backgroundColor: Theme.of(
            context,
          ).colorScheme.primary.withValues(alpha: 0.08),
          child: Icon(
            Icons.restaurant_rounded,
            color: Theme.of(context).colorScheme.primary,
            size: 20,
          ),
        ),
        title: Text(
          product.name,
          style: Theme.of(context).textTheme.titleMedium,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: details.isEmpty
            ? null
            : Text(
                details.join(' · '),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _formatPrice(product.price),
              style: Theme.of(context).textTheme.titleSmall,
            ),
            if (canOrder && product.available)
              IconButton(
                tooltip: 'Agregar al carrito',
                onPressed: onAdd,
                icon: const Icon(Icons.add_shopping_cart_rounded),
              ),
          ],
        ),
        onTap: canOrder && product.available ? onAdd : null,
      ),
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
