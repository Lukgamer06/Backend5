import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../models/reservation.dart';
import '../repositories/reservation_repository.dart';

class ReservationController extends ChangeNotifier {
  ReservationController(this._repository);

  final ReservationRepository _repository;

  List<AvailableTable> _availableTables = const [];
  List<Reservation> _reservations = const [];
  String? _availabilityError;
  String? _reservationError;
  String? _actionError;
  String? _statusFilter;
  bool _isLoadingAvailability = false;
  bool _isLoadingReservations = false;
  bool _isSubmitting = false;
  int _currentPage = 1;
  int _lastPage = 1;
  int _totalReservations = 0;
  int _reservationLoadGeneration = 0;
  int _availabilityLoadGeneration = 0;
  _AvailabilityQuery? _availabilityQuery;

  List<AvailableTable> get availableTables => _availableTables;
  List<Reservation> get reservations => _reservations;
  String? get availabilityError => _availabilityError;
  String? get reservationError => _reservationError;
  String? get actionError => _actionError;
  String? get statusFilter => _statusFilter;
  bool get isLoadingAvailability => _isLoadingAvailability;
  bool get isLoadingReservations => _isLoadingReservations;
  bool get isSubmitting => _isSubmitting;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get totalReservations => _totalReservations;

  Future<void> loadAvailableTables({
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
  }) async {
    final generation = ++_availabilityLoadGeneration;
    final query = _AvailabilityQuery(
      startsAt: startsAt,
      endsAt: endsAt,
      guestCount: guestCount,
    );
    _availabilityQuery = query;
    _availableTables = const [];
    _availabilityError = null;
    _isLoadingAvailability = true;
    notifyListeners();

    try {
      final tables = await _repository.getAvailableTables(
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: guestCount,
      );
      if (generation == _availabilityLoadGeneration) {
        _availableTables = tables;
      }
    } on ApiException catch (error) {
      if (generation == _availabilityLoadGeneration) {
        _availabilityError = error.message;
      }
    } finally {
      if (generation == _availabilityLoadGeneration) {
        _isLoadingAvailability = false;
        notifyListeners();
      }
    }
  }

  void clearAvailability() {
    _availabilityLoadGeneration++;
    _isLoadingAvailability = false;
    _availableTables = const [];
    _availabilityError = null;
    _actionError = null;
    _availabilityQuery = null;
    notifyListeners();
  }

  Future<void> loadReservations({int? page, String? status}) async {
    if (status != null) {
      _statusFilter = status.isEmpty ? null : status;
      _currentPage = 1;
    }
    final requestedPage = page ?? _currentPage;
    final requestedStatus = _statusFilter;
    final generation = ++_reservationLoadGeneration;
    _isLoadingReservations = true;
    _reservationError = null;
    notifyListeners();

    try {
      final result = await _repository.getReservations(
        page: requestedPage,
        status: requestedStatus,
      );
      if (generation == _reservationLoadGeneration) {
        _reservations = result.reservations;
        _currentPage = result.currentPage;
        _lastPage = result.lastPage;
        _totalReservations = result.total;
      }
    } on ApiException catch (error) {
      if (generation == _reservationLoadGeneration) {
        _reservationError = error.message;
      }
    } finally {
      if (generation == _reservationLoadGeneration) {
        _isLoadingReservations = false;
        notifyListeners();
      }
    }
  }

  Future<Reservation?> createReservation({
    required int tableId,
    required DateTime startsAt,
    required DateTime endsAt,
    required int guestCount,
    String? notes,
  }) async {
    if (_isSubmitting) {
      return null;
    }
    if (!startsAt.isAfter(DateTime.now())) {
      _actionError = 'Selecciona una fecha y hora futura.';
      notifyListeners();

      return null;
    }
    if (!endsAt.isAfter(startsAt)) {
      _actionError = 'La hora de fin debe ser posterior a la de inicio.';
      notifyListeners();

      return null;
    }
    if (guestCount < 1 || guestCount > 255) {
      _actionError = 'El número de personas debe estar entre 1 y 255.';
      notifyListeners();

      return null;
    }
    final query = _availabilityQuery;
    if (query == null ||
        query.startsAt != startsAt ||
        query.endsAt != endsAt ||
        query.guestCount != guestCount ||
        !_availableTables.any((table) => table.id == tableId)) {
      _actionError =
          'Consulta de nuevo las mesas disponibles para estos datos.';
      notifyListeners();

      return null;
    }

    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      final reservation = await _repository.createReservation(
        tableId: tableId,
        startsAt: startsAt,
        endsAt: endsAt,
        guestCount: guestCount,
        notes: notes,
      );
      _availableTables = const [];
      await loadReservations(page: 1);

      return reservation;
    } on ApiException catch (error) {
      _actionError = error.message;
      if (error.statusCode == 409) {
        await loadAvailableTables(
          startsAt: query.startsAt,
          endsAt: query.endsAt,
          guestCount: query.guestCount,
        );
      }

      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> cancelReservation(Reservation reservation) async {
    if (_isSubmitting || !reservation.canCancel) {
      return;
    }

    _isSubmitting = true;
    _actionError = null;
    notifyListeners();

    try {
      await _repository.cancelReservation(reservation.id);
      await loadReservations();
    } on ApiException catch (error) {
      _actionError = error.message;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> goToPreviousPage() async {
    if (_currentPage > 1) {
      await loadReservations(page: _currentPage - 1);
    }
  }

  Future<void> goToNextPage() async {
    if (_currentPage < _lastPage) {
      await loadReservations(page: _currentPage + 1);
    }
  }
}

class _AvailabilityQuery {
  const _AvailabilityQuery({
    required this.startsAt,
    required this.endsAt,
    required this.guestCount,
  });

  final DateTime startsAt;
  final DateTime endsAt;
  final int guestCount;
}
