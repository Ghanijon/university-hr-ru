<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;

final class DepartmentController extends Controller
{
    /** GET /departments — справочники кафедр и должностей */
    public function index(): void
    {
        $this->render('departments/index', [
            'title'       => 'Кафедры',
            'active'      => 'departments',
            'departments' => (new DepartmentRepository())->all(),
            'positions'   => (new PositionRepository())->all(),
        ]);
    }
}
