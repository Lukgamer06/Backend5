import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../models/incident.dart';
import '../repositories/incident_repository.dart';

class IncidentController extends ChangeNotifier {
  IncidentController(this._repository);

  final IncidentRepository _repository;

  List<Incident> _incidents = const [];
  String? _error;
  String? _actionError;
  String? _search;
  String? _priority;
  String? _status;
  bool _isLoading = false;
  bool _isSubmitting = false;
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  int _loadGeneration = 0;

  List<Incident> get incidents => _incidents;
  String? get error => _error;
  String? get actionError => _actionError;
  String? get priority => _priority;
  String? get status => _status;
  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get total => _total;

  Future<void> loadIncidents({
    int? page,
    String? search,
    String? priority,
    String? status,
  }) async {
    if (search != null || priority != null || status != null) {
      if (search != null) {
        _search = search.trim().isEmpty ? null : search.trim();
      }
      if (priority != null) {
        _priority = priority.isEmpty ? null : priority;
      }
      if (status != null) {
        _status = status.isEmpty ? null : status;
      }
      _currentPage = 1;
    }

    final requestedPage = page ?? _currentPage;
    final generation = ++_loadGeneration;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _repository.getIncidents(
        page: requestedPage,
        search: _search,
        priority: _priority,
        status: _status,
      );
      if (generation == _loadGeneration) {
        _incidents = result.incidents;
        _currentPage = result.currentPage;
        _lastPage = result.lastPage;
        _total = result.total;
      }
    } on ApiException catch (error) {
      if (generation == _loadGeneration) {
        _error = error.message;
      }
    } finally {
      if (generation == _loadGeneration) {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  Future<Incident?> reportIncident({
    required String title,
    required String description,
    required String priority,
  }) async {
    if (_isSubmitting) {
      return null;
    }
    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      final incident = await _repository.reportIncident(
        title: title,
        description: description,
        priority: priority,
      );
      await loadIncidents(page: 1);

      return incident;
    } on ApiException catch (error) {
      _actionError = error.message;

      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> updateIncident({
    required Incident incident,
    required String priority,
    required String status,
  }) async {
    if (_isSubmitting) {
      return;
    }
    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      await _repository.updateIncident(
        incidentId: incident.id,
        priority: priority,
        status: status,
      );
      await loadIncidents(page: _currentPage);
    } on ApiException catch (error) {
      _actionError = error.message;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> goToPreviousPage() async {
    if (_currentPage > 1) {
      await loadIncidents(page: _currentPage - 1);
    }
  }

  Future<void> goToNextPage() async {
    if (_currentPage < _lastPage) {
      await loadIncidents(page: _currentPage + 1);
    }
  }
}
