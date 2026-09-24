<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('settings/password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdatePasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        // Other sessions keep a copy of the old password hash; invalidate them.
        $request->user()->setRememberToken(null);
        $request->user()->save();

        return back()->with('status', 'Password updated.');
    }
}
