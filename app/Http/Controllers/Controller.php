<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Redirige vers la page d'origine (champ `_back`) si elle est valide
     * (même origine, différente de la requête courante), sinon vers la route de secours.
     */
    protected function redirectBack(string $fallbackRoute, array $params = []): RedirectResponse
    {
        $back = request()->input('_back');

        if ($back && Str::startsWith($back, url('/')) && rtrim($back, '/') !== rtrim(request()->url(), '/')) {
            return redirect()->to($back);
        }

        return redirect()->route($fallbackRoute, $params);
    }
}
