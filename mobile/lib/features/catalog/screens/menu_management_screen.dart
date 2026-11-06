import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/providers/auth_controller.dart';
import '../models/daily_menu.dart';
import '../models/product.dart';
import '../providers/menu_management_controller.dart';

const _menuStatuses = <String, String>{
  'BORRADOR': 'Borrador',
  'PUBLICADO': 'Publicado',
};

class MenuManagementScreen extends StatefulWidget {
  const MenuManagementScreen({super.key});

  @override
  State<MenuManagementScreen> createState() => _MenuManagementScreenState();
}

class _MenuManagementScreenState extends State<MenuManagementScreen> {
  final _productSearchController = TextEditingController();
  DateTime _menuDate = DateTime.now();
  DailyMenu? _editingMenu;
  final Set<int> _selectedProductIds = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) {
        return;
      }
      final role = context.read<AuthController>().user?.rol;
      if (role != 'ADMINISTRADOR' && role != 'OPERADOR') {
        return;
      }
      final controller = context.read<MenuManagementController>();
      controller.loadMenus();
      controller.loadProducts();
    });
  }

  @override
  void dispose() {
    _productSearchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthController>().user?.rol;
    if (role != 'ADMINISTRADOR' && role != 'OPERADOR') {
      return Scaffold(
        appBar: AppBar(title: const Text('Administrar menú del día')),
        body: const Center(
          child: Text(
            'La administración del menú está disponible para personal.',
          ),
        ),
      );
    }

    final controller = context.watch<MenuManagementController>();
    final products = _productsForSelection(controller.products);

    return Scaffold(
      appBar: AppBar(title: const Text('Administrar menú del día')),
      body: RefreshIndicator(
        onRefresh: () => controller.loadMenus(),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              _editingMenu == null ? 'Crear borrador' : 'Editar borrador',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                ListTile(
                  key: const Key('menu-date'),
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Fecha'),
                  subtitle: Text(_formatDate(_menuDate)),
                  trailing: const Icon(Icons.calendar_month),
                  onTap: controller.isSubmitting ? null : _selectDate,
                ),
                TextField(
                  key: const Key('menu-product-search'),
                  controller: _productSearchController,
                  textInputAction: TextInputAction.search,
                  onSubmitted: (value) =>
                      controller.loadProducts(search: value),
                  decoration: InputDecoration(
                    labelText: 'Buscar productos disponibles',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: IconButton(
                      tooltip: 'Limpiar búsqueda',
                      onPressed: () {
                        _productSearchController.clear();
                        controller.loadProducts();
                      },
                      icon: const Icon(Icons.clear),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Productos seleccionados: ${_selectedProductIds.length}',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                if (controller.isLoadingProducts)
                  const LinearProgressIndicator()
                else if (controller.products.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text('No hay productos disponibles para agregar.'),
                  )
                else
                  for (final product in products)
                    CheckboxListTile(
                      key: Key('menu-product-${product.id}'),
                      value: _selectedProductIds.contains(product.id),
                      title: Text(product.name),
                      subtitle: product.available
                          ? null
                          : const Text(
                              'Producto inactivo; ya está en este borrador.',
                            ),
                      controlAffinity: ListTileControlAffinity.leading,
                      onChanged: controller.isSubmitting
                          ? null
                          : (selected) => setState(() {
                              if (selected == true) {
                                if (_selectedProductIds.length < 100) {
                                  _selectedProductIds.add(product.id);
                                }
                              } else {
                                _selectedProductIds.remove(product.id);
                              }
                            }),
                    ),
                if (controller.actionError case final error?)
                  _MenuError(message: error),
                FilledButton(
                  key: const Key('save-menu-draft'),
                  onPressed: controller.isSubmitting ? null : _saveMenu,
                  child: controller.isSubmitting
                      ? const CircularProgressIndicator()
                      : Text(
                          _editingMenu == null
                              ? 'Guardar borrador'
                              : 'Actualizar borrador',
                        ),
                ),
                if (_editingMenu != null)
                  TextButton(
                    onPressed: controller.isSubmitting ? null : _clearForm,
                    child: const Text('Cancelar edición'),
                  ),
              ],
            ),
            const Divider(height: 32),
            Text('Menús', style: Theme.of(context).textTheme.titleLarge),
            DropdownButton<String>(
              key: const Key('menu-status-filter'),
              value: controller.status ?? '',
              onChanged: controller.isLoadingMenus
                  ? null
                  : (status) {
                      if (status != null) {
                        controller.loadMenus(status: status);
                      }
                    },
              items: const [
                DropdownMenuItem(value: '', child: Text('Todos')),
                DropdownMenuItem(value: 'BORRADOR', child: Text('Borradores')),
                DropdownMenuItem(value: 'PUBLICADO', child: Text('Publicados')),
              ],
            ),
            if (controller.isLoadingMenus && controller.menus.isEmpty)
              const Center(child: CircularProgressIndicator())
            else if (controller.error case final error?)
              _MenuError(message: error, onRetry: controller.loadMenus)
            else if (controller.menus.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 16),
                child: Text('No hay menús para mostrar.'),
              )
            else
              for (final menu in controller.menus)
                _MenuTile(
                  menu: menu,
                  isBusy: controller.isSubmitting,
                  onEdit: () => _startEditing(menu),
                  onPublish: () => _publishMenu(controller, menu),
                ),
            if (controller.total > 10)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    tooltip: 'Página anterior',
                    onPressed:
                        controller.isLoadingMenus || controller.currentPage <= 1
                        ? null
                        : controller.goToPreviousPage,
                    icon: const Icon(Icons.chevron_left),
                  ),
                  Text(
                    'Página ${controller.currentPage} de ${controller.lastPage}',
                  ),
                  IconButton(
                    tooltip: 'Página siguiente',
                    onPressed:
                        controller.isLoadingMenus ||
                            controller.currentPage >= controller.lastPage
                        ? null
                        : controller.goToNextPage,
                    icon: const Icon(Icons.chevron_right),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  List<Product> _productsForSelection(List<Product> availableProducts) {
    final productsById = {
      for (final product in availableProducts) product.id: product,
    };
    for (final product in _editingMenu?.products ?? const <Product>[]) {
      productsById.putIfAbsent(product.id, () => product);
    }

    return productsById.values.toList()
      ..sort((first, second) => first.name.compareTo(second.name));
  }

  Future<void> _selectDate() async {
    final selectedDate = await showDatePicker(
      context: context,
      initialDate: _menuDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(DateTime.now().year + 10),
    );
    if (selectedDate != null && mounted) {
      setState(() {
        _menuDate = selectedDate;
      });
    }
  }

  Future<void> _saveMenu() async {
    final menu = await context.read<MenuManagementController>().saveMenu(
      menuId: _editingMenu?.id,
      date: _menuDate,
      productIds: _selectedProductIds.toList()..sort(),
    );
    if (menu == null || !mounted) {
      return;
    }

    _clearForm();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Borrador del ${_formatDate(menu.date)} guardado.'),
      ),
    );
  }

  Future<void> _publishMenu(
    MenuManagementController controller,
    DailyMenu menu,
  ) async {
    final published = await controller.publishMenu(menu.id);
    if (published != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Menú del ${_formatDate(menu.date)} publicado.'),
        ),
      );
    }
  }

  void _startEditing(DailyMenu menu) {
    setState(() {
      _editingMenu = menu;
      _menuDate = menu.date;
      _selectedProductIds
        ..clear()
        ..addAll(menu.products.map((product) => product.id));
    });
  }

  void _clearForm() {
    setState(() {
      _editingMenu = null;
      _menuDate = DateTime.now();
      _selectedProductIds.clear();
      _productSearchController.clear();
    });
    context.read<MenuManagementController>().loadProducts();
  }

  String _formatDate(DateTime date) =>
      '${date.day.toString().padLeft(2, '0')}/'
      '${date.month.toString().padLeft(2, '0')}/'
      '${date.year}';
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({
    required this.menu,
    required this.isBusy,
    required this.onEdit,
    required this.onPublish,
  });

  final DailyMenu menu;
  final bool isBusy;
  final VoidCallback onEdit;
  final VoidCallback onPublish;

  @override
  Widget build(BuildContext context) {
    final isDraft = menu.status == 'BORRADOR';

    return Card(
      child: ExpansionTile(
        key: ValueKey('menu-${menu.id}'),
        title: Text(
          '${_formatDate(menu.date)} · ${_menuStatuses[menu.status] ?? menu.status}',
        ),
        subtitle: Text('${menu.products.length} productos'),
        children: [
          for (final product in menu.products)
            ListTile(
              dense: true,
              title: Text(product.name),
              subtitle: Text('\$${product.price.toStringAsFixed(0)}'),
              trailing: product.available ? null : const Text('Inactivo'),
            ),
          if (isDraft)
            OverflowBar(
              alignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  key: Key('edit-menu-${menu.id}'),
                  onPressed: isBusy ? null : onEdit,
                  child: const Text('Editar borrador'),
                ),
                FilledButton(
                  key: Key('publish-menu-${menu.id}'),
                  onPressed: isBusy ? null : onPublish,
                  child: const Text('Publicar'),
                ),
              ],
            ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) =>
      '${date.day.toString().padLeft(2, '0')}/'
      '${date.month.toString().padLeft(2, '0')}/'
      '${date.year}';
}

class _MenuError extends StatelessWidget {
  const _MenuError({required this.message, this.onRetry});

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            message,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
          if (onRetry != null)
            TextButton(onPressed: onRetry, child: const Text('Reintentar')),
        ],
      ),
    );
  }
}
