import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../auth/providers/auth_controller.dart';
import '../providers/order_controller.dart';

class CreateOrderScreen extends StatefulWidget {
  const CreateOrderScreen({super.key});

  @override
  State<CreateOrderScreen> createState() => _CreateOrderScreenState();
}

class _CreateOrderScreenState extends State<CreateOrderScreen> {
  bool _scheduled = false;
  DateTime? _scheduledAt;

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<OrderController>();
    final user = context.watch<AuthController>().user;

    return Scaffold(
      appBar: AppBar(title: const Text('Confirmar pedido')),
      body: user?.rol != 'CLIENTE'
          ? const Center(
              child: Text(
                'La creación de pedidos está disponible para clientes.',
              ),
            )
          : controller.cartItems.isEmpty
          ? const Center(child: Text('Agrega productos antes de continuar.'))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                for (final item in controller.cartItems)
                  ListTile(
                    title: Text(item.product.name),
                    subtitle: Text(
                      '${item.quantity} × ${_formatPrice(item.product.price)}',
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          key: Key('decrease-${item.product.id}'),
                          tooltip: 'Quitar uno',
                          onPressed: () =>
                              controller.decreaseProduct(item.product.id),
                          icon: const Icon(Icons.remove_circle_outline),
                        ),
                        Text('${item.quantity}'),
                        IconButton(
                          key: Key('increase-${item.product.id}'),
                          tooltip: 'Agregar uno',
                          onPressed: () => controller.addProduct(item.product),
                          icon: const Icon(Icons.add_circle_outline),
                        ),
                      ],
                    ),
                  ),
                const Divider(),
                const ListTile(
                  leading: Icon(Icons.storefront_outlined),
                  title: Text('Modalidad'),
                  subtitle: Text('Sin mesa'),
                ),
                SwitchListTile(
                  title: const Text('Programar pedido'),
                  subtitle: const Text('De forma predeterminada es inmediato'),
                  value: _scheduled,
                  onChanged: controller.isSubmitting
                      ? null
                      : (value) => setState(() {
                          _scheduled = value;
                          _scheduledAt = null;
                        }),
                ),
                if (_scheduled)
                  ListTile(
                    key: const Key('scheduled-date'),
                    title: Text(
                      _scheduledAt == null
                          ? 'Seleccionar fecha y hora'
                          : _formatDate(_scheduledAt!),
                    ),
                    trailing: const Icon(Icons.calendar_today),
                    onTap: controller.isSubmitting ? null : _selectDateTime,
                  ),
                if (controller.error case final error?)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      error,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                const SizedBox(height: 12),
                FilledButton.icon(
                  key: const Key('submit-order'),
                  onPressed: controller.isSubmitting
                      ? null
                      : () => _submit(context, controller),
                  icon: controller.isSubmitting
                      ? const SizedBox(
                          height: 18,
                          width: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.check),
                  label: Text(
                    controller.isSubmitting ? 'Enviando…' : 'Crear pedido',
                  ),
                ),
              ],
            ),
    );
  }

  Future<void> _selectDateTime() async {
    final now = DateTime.now();
    final firstDate = now.add(const Duration(minutes: 1));
    final date = await showDatePicker(
      context: context,
      initialDate: _scheduledAt ?? firstDate,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: DateTime(now.year + 2),
    );
    if (date == null || !mounted) {
      return;
    }

    final time = await showTimePicker(
      context: context,
      initialTime: _scheduledAt == null
          ? TimeOfDay.fromDateTime(firstDate)
          : TimeOfDay.fromDateTime(_scheduledAt!),
    );
    if (time == null || !mounted) {
      return;
    }

    final scheduledAt = DateTime(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    setState(() {
      _scheduledAt = scheduledAt;
    });
  }

  Future<void> _submit(BuildContext context, OrderController controller) async {
    final order = await controller.submitOrder(
      scheduled: _scheduled,
      scheduledAt: _scheduledAt,
    );
    if (order != null && context.mounted) {
      context.go('/pedidos');
    }
  }
}

String _formatPrice(double price) => '\$${price.toStringAsFixed(2)}';

String _formatDate(DateTime date) =>
    '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year} '
    '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
