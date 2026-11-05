<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\DashboardQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DashboardController
{
    public function __invoke(Request $request, DashboardQueryService $dashboardQueryService): View
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new UnauthorizedHttpException('web');
        }

        return view('dashboard.index', [
            ...$dashboardQueryService->overview($usuario),
            'breadcrumbs' => [
                ['label' => 'Panel principal'],
            ],
        ]);
    }
}
