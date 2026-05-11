<?php

namespace App\Http\Controllers;

use App\Support\WorkspaceAccess;

abstract class Controller
{
    protected function workspace(): WorkspaceAccess
    {
        return app(WorkspaceAccess::class);
    }
}
