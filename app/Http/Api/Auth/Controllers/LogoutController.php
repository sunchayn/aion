<?php

namespace App\Http\Api\Auth\Controllers;

use App\Modules\Auth\Actions\LogoutAction;
use Illuminate\Http\Response;

class LogoutController
{
    public function __invoke(LogoutAction $logoutAction): Response
    {
        $logoutAction->execute();

        return response()->noContent();
    }
}
