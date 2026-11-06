import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/providers/auth_controller.dart';
import '../models/incident.dart';
import '../providers/incident_controller.dart';

const _incidentPriorities = <String, String>{
  'BAJA': 'Baja',
  'MEDIA': 'Media',
  'ALTA': 'Alta',
  'CRITICA': 'Crítica',
};

const _incidentStatuses = <String, String>{
  'ABIERTA': 'Abierta',
  'EN_ATENCION': 'En atención',
  'RESUELTA': 'Resuelta',
};

class IncidentScreen extends StatefulWidget {
  const IncidentScreen({super.key});

  @override
  State<IncidentScreen> createState() => _IncidentScreenState();
}

class _IncidentScreenState extends State<IncidentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _searchController = TextEditingController();
  String _newPriority = 'MEDIA';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) {
        return;
      }
      final role = context.read<AuthController>().user?.rol;
      if (role == 'ADMINISTRADOR' || role == 'OPERADOR') {
        context.read<IncidentController>().loadIncidents();
      }
    });
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthController>().user;
    if (user?.rol != 'ADMINISTRADOR' && user?.rol != 'OPERADOR') {
      return Scaffold(
        appBar: AppBar(title: const Text('Incidencias')),
        body: const Center(
          child: Text(
            'La gestión de incidencias está disponible para personal.',
          ),
        ),
      );
    }

    final controller = context.watch<IncidentController>();
    return Scaffold(
      appBar: AppBar(title: const Text('Incidencias')),
      body: RefreshIndicator(
        onRefresh: controller.loadIncidents,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              'Reportar incidencia',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextFormField(
                    key: const Key('incident-title'),
                    controller: _titleController,
                    maxLength: 150,
                    decoration: const InputDecoration(labelText: 'Título'),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Ingresa un título.'
                        : null,
                  ),
                  TextFormField(
                    key: const Key('incident-description'),
                    controller: _descriptionController,
                    maxLength: 65535,
                    minLines: 2,
                    maxLines: 4,
                    decoration: const InputDecoration(
                      labelText: 'Descripción',
                      alignLabelWithHint: true,
                      counterText: '',
                    ),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Ingresa una descripción.'
                        : null,
                  ),
                  DropdownButtonFormField<String>(
                    key: const Key('incident-create-priority'),
                    initialValue: _newPriority,
                    decoration: const InputDecoration(labelText: 'Prioridad'),
                    items: [
                      for (final entry in _incidentPriorities.entries)
                        DropdownMenuItem(
                          value: entry.key,
                          child: Text(entry.value),
                        ),
                    ],
                    onChanged: controller.isSubmitting
                        ? null
                        : (value) {
                            if (value != null) {
                              setState(() => _newPriority = value);
                            }
                          },
                  ),
                  const SizedBox(height: 8),
                  FilledButton(
                    key: const Key('report-incident'),
                    onPressed: controller.isSubmitting ? null : _reportIncident,
                    child: controller.isSubmitting
                        ? const CircularProgressIndicator()
                        : const Text('Reportar'),
                  ),
                ],
              ),
            ),
            if (controller.actionError case final error?)
              _IncidentError(message: error),
            const Divider(height: 32),
            Text(
              'Lista de incidencias',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            TextField(
              key: const Key('incident-search'),
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: (value) => controller.loadIncidents(search: value),
              decoration: InputDecoration(
                labelText: 'Buscar incidencias',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  tooltip: 'Limpiar búsqueda',
                  onPressed: () {
                    _searchController.clear();
                    controller.loadIncidents(search: '');
                  },
                  icon: const Icon(Icons.clear),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 12,
              children: [
                DropdownButton<String>(
                  key: const Key('incident-status-filter'),
                  value: controller.status ?? '',
                  onChanged: controller.isLoading
                      ? null
                      : (value) {
                          if (value != null) {
                            controller.loadIncidents(status: value);
                          }
                        },
                  items: const [
                    DropdownMenuItem(
                      value: '',
                      child: Text('Todos los estados'),
                    ),
                    ...[
                      DropdownMenuItem(
                        value: 'ABIERTA',
                        child: Text('Abiertas'),
                      ),
                      DropdownMenuItem(
                        value: 'EN_ATENCION',
                        child: Text('En atención'),
                      ),
                      DropdownMenuItem(
                        value: 'RESUELTA',
                        child: Text('Resueltas'),
                      ),
                    ],
                  ],
                ),
                DropdownButton<String>(
                  key: const Key('incident-priority-filter'),
                  value: controller.priority ?? '',
                  onChanged: controller.isLoading
                      ? null
                      : (value) {
                          if (value != null) {
                            controller.loadIncidents(priority: value);
                          }
                        },
                  items: const [
                    DropdownMenuItem(
                      value: '',
                      child: Text('Todas prioridades'),
                    ),
                    DropdownMenuItem(value: 'BAJA', child: Text('Baja')),
                    DropdownMenuItem(value: 'MEDIA', child: Text('Media')),
                    DropdownMenuItem(value: 'ALTA', child: Text('Alta')),
                    DropdownMenuItem(value: 'CRITICA', child: Text('Crítica')),
                  ],
                ),
              ],
            ),
            if (controller.isLoading && controller.incidents.isEmpty)
              const Center(child: CircularProgressIndicator())
            else if (controller.error case final error?)
              _IncidentError(message: error, onRetry: controller.loadIncidents)
            else if (controller.incidents.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 16),
                child: Text('No hay incidencias para mostrar.'),
              )
            else
              for (final incident in controller.incidents)
                _IncidentTile(
                  incident: incident,
                  isBusy: controller.isSubmitting,
                  onUpdate: (priority, status) => controller.updateIncident(
                    incident: incident,
                    priority: priority,
                    status: status,
                  ),
                ),
            if (controller.total > 10)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    tooltip: 'Página anterior',
                    onPressed:
                        controller.isLoading || controller.currentPage <= 1
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
                        controller.isLoading ||
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

  Future<void> _reportIncident() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final incident = await context.read<IncidentController>().reportIncident(
      title: _titleController.text,
      description: _descriptionController.text,
      priority: _newPriority,
    );
    if (incident == null || !mounted) {
      return;
    }

    _titleController.clear();
    _descriptionController.clear();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Incidencia #${incident.id} reportada.')),
    );
  }
}

class _IncidentTile extends StatelessWidget {
  const _IncidentTile({
    required this.incident,
    required this.isBusy,
    required this.onUpdate,
  });

  final Incident incident;
  final bool isBusy;
  final void Function(String priority, String status) onUpdate;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              incident.title,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 4),
            Text(incident.description),
            if (incident.reporterName case final name?)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text('Reportada por: $name'),
              ),
            Wrap(
              spacing: 12,
              children: [
                DropdownButton<String>(
                  key: Key('incident-status-${incident.id}'),
                  value: incident.status,
                  onChanged: isBusy
                      ? null
                      : (status) {
                          if (status != null) {
                            onUpdate(incident.priority, status);
                          }
                        },
                  items: [
                    for (final entry in _incidentStatuses.entries)
                      DropdownMenuItem(
                        value: entry.key,
                        child: Text(entry.value),
                      ),
                  ],
                ),
                DropdownButton<String>(
                  key: Key('incident-priority-${incident.id}'),
                  value: incident.priority,
                  onChanged: isBusy
                      ? null
                      : (priority) {
                          if (priority != null) {
                            onUpdate(priority, incident.status);
                          }
                        },
                  items: [
                    for (final entry in _incidentPriorities.entries)
                      DropdownMenuItem(
                        value: entry.key,
                        child: Text(entry.value),
                      ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _IncidentError extends StatelessWidget {
  const _IncidentError({required this.message, this.onRetry});

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
