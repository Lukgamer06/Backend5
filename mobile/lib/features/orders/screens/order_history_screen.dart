import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../models/order.dart';
import '../providers/order_controller.dart';

class OrderHistoryScreen extends StatefulWidget {
  const OrderHistoryScreen({super.key});

  @override
  State<OrderHistoryScreen> createState() => _OrderHistoryScreenState();
}

class _OrderHistoryScreenState extends State<OrderHistoryScreen> {
  static const _statuses = <String, String>{
    '': 'Todos',
    'REGISTRADO': 'Registrados',
    'EN_PREPARACION': 'En preparación',
    'LISTO': 'Listos',
    'ENTREGADO': 'Entregados',
    'CANCELADO': 'Cancelados',
  };

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<OrderController>().loadOrders();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<OrderController>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pedidos'),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Filtrar por estado',
            initialValue: controller.statusFilter ?? '',
            onSelected: (status) => controller.loadOrders(status: status),
            itemBuilder: (context) => [
              for (final entry in _statuses.entries)
                PopupMenuItem(value: entry.key, child: Text(entry.value)),
            ],
            icon: const Icon(Icons.filter_list),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: controller.loadOrders,
        child: _buildBody(context, controller),
      ),
      floatingActionButton: controller.cartItemCount == 0
          ? null
          : FloatingActionButton.extended(
              key: const Key('open-cart'),
              onPressed: () => context.push('/pedidos/nuevo'),
              icon: const Icon(Icons.shopping_cart_checkout),
              label: Text('Carrito (${controller.cartItemCount})'),
            ),
    );
  }

  Widget _buildBody(BuildContext context, OrderController controller) {
    if (controller.isLoadingOrders && controller.orders.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (controller.error != null && controller.orders.isEmpty) {
      return _CenteredMessage(
        message: controller.error!,
        action: TextButton(
          onPressed: controller.loadOrders,
          child: const Text('Reintentar'),
        ),
      );
    }

    if (controller.orders.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 180),
          Center(child: Text('Todavía no tienes pedidos.')),
        ],
      );
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.only(bottom: 96),
      children: [
        if (controller.error case final error?)
          Padding(
            padding: const EdgeInsets.all(12),
            child: Text(
              error,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ),
        for (final order in controller.orders)
          _OrderCard(
            order: order,
            isBusy: controller.isSubmitting,
            onCancel: () => _confirmCancellation(context, controller, order),
          ),
        if (controller.totalOrders > 10)
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              IconButton(
                tooltip: 'Página anterior',
                onPressed:
                    controller.isLoadingOrders || controller.currentPage <= 1
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
                    controller.isLoadingOrders ||
                        controller.currentPage >= controller.lastPage
                    ? null
                    : controller.goToNextPage,
                icon: const Icon(Icons.chevron_right),
              ),
            ],
          ),
      ],
    );
  }

  Future<void> _confirmCancellation(
    BuildContext context,
    OrderController controller,
    CustomerOrder order,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar pedido'),
        content: Text(
          '¿Deseas solicitar la cancelación del pedido #${order.id}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Volver'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cancelar pedido'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      await controller.cancelOrder(order);
    }
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({
    required this.order,
    required this.isBusy,
    required this.onCancel,
  });

  final CustomerOrder order;
  final bool isBusy;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      child: ExpansionTile(
        key: ValueKey('order-${order.id}'),
        title: Text('Pedido #${order.id} · ${_orderStatusLabel(order.status)}'),
        subtitle: Text(
          '${order.attentionType == 'PROGRAMADO' ? 'Programado' : 'Inmediato'} · ${_formatPrice(order.total)}',
        ),
        children: [
          for (final line in order.lines)
            ListTile(
              dense: true,
              title: Text(line.name),
              subtitle: Text(
                '${line.quantity} × ${_formatPrice(line.unitPrice)}',
              ),
              trailing: Text(_formatPrice(line.subtotal)),
            ),
          if (order.scheduledAt case final date?)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              child: Text('Entrega programada: ${_formatDate(date)}'),
            ),
          if (order.canRequestCancellation)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: isBusy ? null : onCancel,
                icon: const Icon(Icons.cancel_outlined),
                label: const Text('Solicitar cancelación'),
              ),
            ),
        ],
      ),
    );
  }
}

class _CenteredMessage extends StatelessWidget {
  const _CenteredMessage({required this.message, this.action});

  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 180),
        Center(child: Text(message, textAlign: TextAlign.center)),
        if (action != null) Center(child: action),
      ],
    );
  }
}

String _orderStatusLabel(String status) {
  return switch (status) {
    'REGISTRADO' => 'Registrado',
    'EN_PREPARACION' => 'En preparación',
    'LISTO' => 'Listo',
    'ENTREGADO' => 'Entregado',
    'CANCELADO' => 'Cancelado',
    _ => status,
  };
}

String _formatPrice(double price) => '\$${price.toStringAsFixed(2)}';

String _formatDate(DateTime date) =>
    '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year} '
    '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
