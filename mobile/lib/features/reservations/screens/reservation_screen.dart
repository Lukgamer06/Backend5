import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/providers/auth_controller.dart';
import '../models/reservation.dart';
import '../providers/reservation_controller.dart';

class ReservationScreen extends StatefulWidget {
  const ReservationScreen({super.key});

  @override
  State<ReservationScreen> createState() => _ReservationScreenState();
}

class _ReservationScreenState extends State<ReservationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _guestCountController = TextEditingController(text: '2');
  late DateTime _startsAt;
  late DateTime _endsAt;
  int? _selectedTableId;
  String? _dateError;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _startsAt = DateTime(now.year, now.month, now.day, now.hour + 2);
    _endsAt = _startsAt.add(const Duration(hours: 1));
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<ReservationController>().loadReservations();
      }
    });
  }

  @override
  void dispose() {
    _guestCountController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthController>().user;
    if (user?.rol != 'CLIENTE') {
      return Scaffold(
        appBar: AppBar(title: const Text('Mis reservas')),
        body: const Center(
          child: Text('Las reservas móviles están disponibles para clientes.'),
        ),
      );
    }

    final controller = context.watch<ReservationController>();
    return Scaffold(
      appBar: AppBar(title: const Text('Mis reservas')),
      body: RefreshIndicator(
        onRefresh: controller.loadReservations,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              'Reservar una mesa',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 12),
            Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _DateTimeField(
                    key: const Key('reservation-start'),
                    label: 'Inicio',
                    value: _formatDateTime(_startsAt),
                    onTap: () => _selectDateTime(isStart: true),
                  ),
                  if (_dateError != null)
                    Text(
                      _dateError!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  _DateTimeField(
                    key: const Key('reservation-end'),
                    label: 'Fin',
                    value: _formatDateTime(_endsAt),
                    onTap: () => _selectDateTime(isStart: false),
                  ),
                  TextFormField(
                    key: const Key('reservation-guests'),
                    controller: _guestCountController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Número de personas',
                    ),
                    validator: (value) {
                      final count = int.tryParse(value ?? '');
                      if (count == null || count < 1 || count > 255) {
                        return 'Ingresa un número entre 1 y 255.';
                      }

                      return null;
                    },
                    onChanged: (_) => _invalidateAvailability(),
                  ),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    key: const Key('check-table-availability'),
                    onPressed: controller.isLoadingAvailability
                        ? null
                        : _queryAvailability,
                    icon: controller.isLoadingAvailability
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.search),
                    label: const Text('Consultar mesas disponibles'),
                  ),
                ],
              ),
            ),
            if (controller.availabilityError case final error?)
              _ErrorText(message: error, onRetry: _queryAvailability),
            if (controller.availableTables.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Selecciona una mesa',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              RadioGroup<int>(
                groupValue: _selectedTableId,
                onChanged: (value) => setState(() {
                  _selectedTableId = value;
                }),
                child: Column(
                  children: [
                    for (final table in controller.availableTables)
                      RadioListTile<int>(
                        key: Key('available-table-${table.id}'),
                        value: table.id,
                        title: Text('Mesa ${table.number}'),
                        subtitle: Text('Capacidad: ${table.capacity} personas'),
                      ),
                  ],
                ),
              ),
              FilledButton(
                key: const Key('create-reservation'),
                onPressed: controller.isSubmitting || _selectedTableId == null
                    ? null
                    : _createReservation,
                child: controller.isSubmitting
                    ? const CircularProgressIndicator()
                    : const Text('Confirmar reserva'),
              ),
            ] else if (!controller.isLoadingAvailability &&
                controller.availabilityError == null &&
                _hasQueriedAvailability(controller))
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text(
                  'No hay mesas disponibles para ese horario y aforo.',
                ),
              ),
            if (controller.actionError case final error?)
              _ErrorText(message: error),
            const Divider(height: 32),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Reservas', style: Theme.of(context).textTheme.titleLarge),
                DropdownButton<String>(
                  key: const Key('reservation-status-filter'),
                  value: controller.statusFilter ?? '',
                  onChanged: controller.isLoadingReservations
                      ? null
                      : (status) {
                          if (status != null) {
                            controller.loadReservations(status: status);
                          }
                        },
                  items: const [
                    DropdownMenuItem(value: '', child: Text('Todas')),
                    DropdownMenuItem(
                      value: 'CONFIRMADA',
                      child: Text('Confirmadas'),
                    ),
                    DropdownMenuItem(value: 'ACTIVA', child: Text('Activas')),
                    DropdownMenuItem(
                      value: 'FINALIZADA',
                      child: Text('Finalizadas'),
                    ),
                    DropdownMenuItem(
                      value: 'CANCELADA',
                      child: Text('Canceladas'),
                    ),
                  ],
                ),
              ],
            ),
            if (controller.isLoadingReservations &&
                controller.reservations.isEmpty)
              const Center(child: CircularProgressIndicator())
            else if (controller.reservationError case final error?)
              _ErrorText(message: error, onRetry: controller.loadReservations)
            else if (controller.reservations.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('Todavía no tienes reservas.'),
              )
            else
              for (final reservation in controller.reservations)
                _ReservationTile(
                  reservation: reservation,
                  isBusy: controller.isSubmitting,
                  onCancel: () =>
                      _confirmCancellation(context, controller, reservation),
                ),
            if (controller.totalReservations > 10)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    tooltip: 'Página anterior',
                    onPressed:
                        controller.isLoadingReservations ||
                            controller.currentPage <= 1
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
                        controller.isLoadingReservations ||
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

  bool _hasQueriedAvailability(ReservationController controller) =>
      controller.availabilityError == null &&
      !controller.isLoadingAvailability &&
      controller.availableTables.isEmpty &&
      _selectedTableId == null &&
      _availabilityWasRequested;

  bool _availabilityWasRequested = false;

  Future<void> _queryAvailability() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    if (!_startsAt.isAfter(DateTime.now())) {
      setState(() {
        _dateError = 'Selecciona una fecha y hora futura.';
      });

      return;
    }
    if (!_endsAt.isAfter(_startsAt)) {
      setState(() {
        _dateError = 'La hora de fin debe ser posterior a la de inicio.';
      });

      return;
    }
    final guestCount = int.parse(_guestCountController.text);
    setState(() {
      _dateError = null;
      _selectedTableId = null;
      _availabilityWasRequested = true;
    });
    context.read<ReservationController>().clearAvailability();
    await context.read<ReservationController>().loadAvailableTables(
      startsAt: _startsAt,
      endsAt: _endsAt,
      guestCount: guestCount,
    );
  }

  Future<void> _createReservation() async {
    final controller = context.read<ReservationController>();
    setState(() {
      _availabilityWasRequested = false;
    });
    final reservation = await controller.createReservation(
      tableId: _selectedTableId!,
      startsAt: _startsAt,
      endsAt: _endsAt,
      guestCount: int.parse(_guestCountController.text),
    );
    if (reservation == null || !mounted) {
      return;
    }
    setState(() {
      _selectedTableId = null;
      _availabilityWasRequested = false;
      _dateError = null;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Reserva #${reservation.id} confirmada.')),
    );
  }

  void _invalidateAvailability() {
    setState(() {
      _selectedTableId = null;
      _availabilityWasRequested = false;
    });
    context.read<ReservationController>().clearAvailability();
  }

  Future<void> _selectDateTime({required bool isStart}) async {
    final current = isStart ? _startsAt : _endsAt;
    final now = DateTime.now();
    final selectedDate = await showDatePicker(
      context: context,
      initialDate: current.isBefore(DateTime(now.year, now.month, now.day))
          ? now
          : current,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: DateTime(now.year + 2, now.month, now.day),
    );
    if (selectedDate == null || !mounted) {
      return;
    }

    final selectedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(current),
    );
    if (selectedTime == null || !mounted) {
      return;
    }

    final selected = DateTime(
      selectedDate.year,
      selectedDate.month,
      selectedDate.day,
      selectedTime.hour,
      selectedTime.minute,
    );
    setState(() {
      if (isStart) {
        _startsAt = selected;
      } else {
        _endsAt = selected;
      }
      _selectedTableId = null;
      _availabilityWasRequested = false;
      _dateError = null;
    });
    context.read<ReservationController>().clearAvailability();
  }

  Future<void> _confirmCancellation(
    BuildContext context,
    ReservationController controller,
    Reservation reservation,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar reserva'),
        content: Text('¿Deseas cancelar la reserva #${reservation.id}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Volver'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cancelar reserva'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      await controller.cancelReservation(reservation);
    }
  }
}

class _DateTimeField extends StatelessWidget {
  const _DateTimeField({
    super.key,
    required this.label,
    required this.value,
    required this.onTap,
  });

  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(label),
      subtitle: Text(value),
      trailing: const Icon(Icons.calendar_month),
      onTap: onTap,
    );
  }
}

class _ReservationTile extends StatelessWidget {
  const _ReservationTile({
    required this.reservation,
    required this.isBusy,
    required this.onCancel,
  });

  final Reservation reservation;
  final bool isBusy;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(
          'Reserva #${reservation.id} · '
          '${reservation.tableNumber == null ? 'Mesa ID ${reservation.tableId}' : 'Mesa ${reservation.tableNumber}'}',
        ),
        subtitle: Text(
          '${_reservationStatusLabel(reservation.status)} · '
          '${_formatDateTime(reservation.startsAt)} – '
          '${_formatDateTime(reservation.endsAt)} · '
          '${reservation.guestCount} personas'
          '${reservation.notes == null ? '' : '\n${reservation.notes}'}',
        ),
        isThreeLine: reservation.notes != null,
        trailing: reservation.canCancel
            ? IconButton(
                key: Key('cancel-reservation-${reservation.id}'),
                tooltip: 'Cancelar reserva',
                onPressed: isBusy ? null : onCancel,
                icon: const Icon(Icons.cancel_outlined),
              )
            : null,
      ),
    );
  }
}

class _ErrorText extends StatelessWidget {
  const _ErrorText({required this.message, this.onRetry});

  final String message;
  final Future<void> Function()? onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: Text(
              message,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ),
          if (onRetry != null)
            IconButton(
              tooltip: 'Reintentar',
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
            ),
        ],
      ),
    );
  }
}

String _reservationStatusLabel(String status) => switch (status) {
  'CONFIRMADA' => 'Confirmada',
  'ACTIVA' => 'Activa',
  'FINALIZADA' => 'Finalizada',
  'CANCELADA' => 'Cancelada',
  _ => status,
};

String _formatDateTime(DateTime value) {
  final local = value.toLocal();
  return '${local.day.toString().padLeft(2, '0')}/'
      '${local.month.toString().padLeft(2, '0')}/'
      '${local.year} ${local.hour.toString().padLeft(2, '0')}:'
      '${local.minute.toString().padLeft(2, '0')}';
}
