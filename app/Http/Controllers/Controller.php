<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Exceptions\InvalidRouteException;

abstract class Controller
{
    protected function backWithQueryString(string $message, string $key = 'status'): RedirectResponse
    {
        try {
            $previousUrl = url()->previous();
        } catch (\Throwable) {
            $previousUrl = url('/');
        }

        return redirect($previousUrl)->with($key, $message);
    }
}
