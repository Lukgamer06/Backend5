import '../../../core/network/api_exception.dart';

class AuthUser {
  const AuthUser({
    required this.id,
    required this.nombres,
    required this.apellidos,
    required this.correo,
    required this.rol,
    this.telefono,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    final nombres = json['nombres'];
    final apellidos = json['apellidos'];
    final correo = json['correo'];
    final telefono = json['telefono'];
    final rol = json['rol'];

    if (id is! int ||
        nombres is! String ||
        apellidos is! String ||
        correo is! String ||
        (telefono != null && telefono is! String) ||
        rol is! String) {
      throw const ApiException(
        message: 'El servidor devolvió un perfil de usuario no válido.',
      );
    }

    return AuthUser(
      id: id,
      nombres: nombres,
      apellidos: apellidos,
      correo: correo,
      telefono: telefono is String ? telefono : null,
      rol: rol,
    );
  }

  final int id;
  final String nombres;
  final String apellidos;
  final String correo;
  final String rol;
  final String? telefono;

  String get nombreCompleto => '$nombres $apellidos';
}
