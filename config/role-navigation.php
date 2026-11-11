<?php

declare(strict_types=1);

return [
    'ADMINISTRADOR' => [
        'Reservas' => ['route' => 'reservations.index', 'active' => 'reservations.*'],
        'Pedidos' => ['route' => 'orders.index', 'active' => 'orders.*'],
        'Productos' => ['route' => 'products.index', 'active' => 'products.*'],
        'Menú del día' => ['route' => 'menus.index', 'active' => 'menus.*'],
        'Incidencias' => ['route' => 'incidents.index', 'active' => 'incidents.*'],
        'Pagos' => ['route' => 'payments.index', 'active' => 'payments.*'],
    ],
    'OPERADOR' => [
        'Reservas' => ['route' => 'reservations.index', 'active' => 'reservations.*'],
        'Pedidos' => ['route' => 'orders.index', 'active' => 'orders.*'],
        'Menú del día' => ['route' => 'menus.index', 'active' => 'menus.*'],
        'Incidencias' => ['route' => 'incidents.index', 'active' => 'incidents.*'],
    ],
    'CLIENTE' => [
        'Mis reservas' => ['route' => 'reservations.index', 'active' => 'reservations.*'],
        'Mis pedidos' => ['route' => 'orders.index', 'active' => 'orders.*'],
    ],
];
