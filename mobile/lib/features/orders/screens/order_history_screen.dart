import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../auth/providers/auth_controller.dart';
import '../models/order.dart';
import '../providers/order_controller.dart';
import '../../payments/models/payment.dart';
import '../../payments/providers/payment_controller.dart';

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
    final paymentController = context.watch<PaymentController>();
    final role = context.watch<AuthController>().user?.rol;
    final isStaff = role == 'ADMINISTRADOR' || role == 'OPERADOR';

    return Scaffold(
      appBar: AppBar(
        title: Text(isStaff ? 'Gestión de pedidos' : 'Pedidos'),
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
        child: _buildBody(context, controller, paymentController, isStaff),
      ),
      floatingActionButton: role != 'CLIENTE' || controller.cartItemCount == 0
          ? null
          : FloatingActionButton.extended(
              key: const Key('open-cart'),
              onPressed: () => context.push('/pedidos/nuevo'),
              icon: const Icon(Icons.shopping_cart_checkout),
              label: Text('Carrito (${controller.cartItemCount})'),
            ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    OrderController controller,
    PaymentController paymentController,
    bool isStaff,
  ) {
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
            isStaff: isStaff,
            paymentController: paymentController,
            onCancel: () => _confirmCancellation(context, controller, order),
            onChangeStatus: (status) =>
                _changeOrderStatus(context, controller, order, status),
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

  Future<void> _changeOrderStatus(
    BuildContext context,
    OrderController controller,
    CustomerOrder order,
    String status,
  ) async {
    final updatedOrder = await controller.changeOrderStatus(order, status);
    if (updatedOrder == null || !context.mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          'Pedido #${updatedOrder.id}: ${_orderStatusLabel(updatedOrder.status)}.',
        ),
      ),
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
    required this.isStaff,
    required this.paymentController,
    required this.onCancel,
    required this.onChangeStatus,
  });

  final CustomerOrder order;
  final bool isBusy;
  final bool isStaff;
  final PaymentController paymentController;
  final VoidCallback onCancel;
  final ValueChanged<String> onChangeStatus;

  @override
  Widget build(BuildContext context) {
    final nextStatus = order.nextProgressionStatus;

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      child: ExpansionTile(
        key: ValueKey('order-${order.id}'),
        onExpansionChanged: (expanded) {
          if (expanded && !isStaff) {
            paymentController.loadForOrder(order.id);
          }
        },
        title: Text('Pedido #${order.id} · ${_orderStatusLabel(order.status)}'),
        subtitle: Text(
          [
            if (isStaff && order.customerName != null) order.customerName!,
            order.attentionType == 'PROGRAMADO' ? 'Programado' : 'Inmediato',
            _formatPrice(order.total),
          ].join(' · '),
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
          if (isStaff && nextStatus != null)
            Align(
              alignment: Alignment.centerRight,
              child: Padding(
                padding: const EdgeInsets.only(right: 12, bottom: 8),
                child: OutlinedButton.icon(
                  key: Key('advance-order-${order.id}'),
                  onPressed: isBusy ? null : () => onChangeStatus(nextStatus),
                  icon: const Icon(Icons.arrow_forward),
                  label: Text(_nextStatusActionLabel(nextStatus)),
                ),
              ),
            ),
          if (!isStaff)
            _PaymentSection(order: order, controller: paymentController),
          if (!isStaff && order.canRequestCancellation)
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

class _PaymentSection extends StatelessWidget {
  const _PaymentSection({required this.order, required this.controller});

  final CustomerOrder order;
  final PaymentController controller;

  @override
  Widget build(BuildContext context) {
    final payments = controller.paymentsFor(order.id);
    final isBusy = controller.isSubmitting(order.id);
    final isPending = controller.hasPendingPayment(order.id);
    final canSubmit =
        controller.isLoaded(order.id) &&
        !controller.isLoading(order.id) &&
        !isBusy &&
        !isPending;
    final error = controller.errorFor(order.id);

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Pagos', style: Theme.of(context).textTheme.titleMedium),
              TextButton.icon(
                key: Key('refresh-payments-${order.id}'),
                onPressed: controller.isLoading(order.id)
                    ? null
                    : () => controller.loadForOrder(order.id),
                icon: const Icon(Icons.refresh),
                label: const Text('Actualizar'),
              ),
            ],
          ),
          if (controller.isLoading(order.id))
            const LinearProgressIndicator()
          else if (error != null)
            Text(
              error,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            )
          else if (payments.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text('Este pedido todavía no tiene pagos registrados.'),
            )
          else
            for (final payment in payments) _PaymentTile(payment: payment),
          if (isPending)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(
                'Hay un pago pendiente de confirmación. No envíes otra solicitud.',
              ),
            ),
          if (error != null && !isPending)
            TextButton(
              onPressed: isBusy
                  ? null
                  : () => controller.loadForOrder(order.id),
              child: const Text('Reintentar consulta'),
            )
          else if (canSubmit || isPending) ...[
            Wrap(
              spacing: 8,
              children: [
                OutlinedButton(
                  key: Key('online-payment-${order.id}'),
                  onPressed: isBusy || isPending
                      ? null
                      : () => _submitPayment(
                          context,
                          controller.requestOnlinePayment(order.id),
                        ),
                  child: const Text('Solicitar pago en línea'),
                ),
                OutlinedButton(
                  key: Key('in-person-payment-${order.id}'),
                  onPressed: isBusy || isPending
                      ? null
                      : () => _submitPayment(
                          context,
                          controller.registerInPersonPayment(order.id),
                        ),
                  child: const Text('Registrar pago presencial'),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _submitPayment(
    BuildContext context,
    Future<Payment?> submission,
  ) async {
    final payment = await submission;
    if (payment == null || !context.mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          payment.isPending
              ? 'Solicitud registrada. El pago está pendiente de confirmación.'
              : 'Pago registrado: ${_paymentStatusLabel(payment.status)}.',
        ),
      ),
    );
  }
}

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({required this.payment});

  final Payment payment;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      dense: true,
      title: Text(
        '${_paymentChannelLabel(payment.channel)} · ${_paymentStatusLabel(payment.status)}',
      ),
      subtitle: Text('Referencia: ${payment.reference}'),
      trailing: Text(_formatPrice(payment.amount)),
    );
  }
}

String _paymentChannelLabel(String channel) => switch (channel) {
  'EN_LINEA' => 'En línea',
  'PRESENCIAL' => 'Presencial',
  _ => channel,
};

String _nextStatusActionLabel(String status) => switch (status) {
  'EN_PREPARACION' => 'Iniciar preparación',
  'LISTO' => 'Marcar como listo',
  'ENTREGADO' => 'Marcar como entregado',
  _ => status,
};

String _paymentStatusLabel(String status) => switch (status) {
  'PENDIENTE' => 'Pendiente',
  'APROBADO' => 'Aprobado',
  'RECHAZADO' => 'Rechazado',
  'EXPIRADO' => 'Expirado',
  'CANCELADO' => 'Cancelado',
  'REEMBOLSADO' => 'Reembolsado',
  _ => status,
};

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
