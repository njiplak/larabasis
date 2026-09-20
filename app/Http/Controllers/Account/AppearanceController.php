<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AppearanceController extends Controller
{
    /**
     * Appearance is stored in a cookie and applied client side; this only
     * renders the picker.
     */
    public function edit()
    {
        return Inertia::render('settings/appearance');
    }
}
