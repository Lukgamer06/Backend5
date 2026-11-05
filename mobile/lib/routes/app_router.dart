import 'package:go_router/go_router.dart';

import '../widgets/app_bootstrap_view.dart';

GoRouter createAppRouter() {
  return GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        builder: (context, state) => const AppBootstrapView(),
      ),
    ],
  );
}
