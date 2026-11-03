<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\SettingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    use RespondsWithJson;

    public function index()
    {
        return view('setting');
    }

    public function update(SettingRequest $request)
    {
        // Ganti remember_token agar sesi "ingat saya" di perangkat lain tidak berlaku lagi
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('pbaru')),
            'remember_token' => Str::random(60),
        ])->save();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Kata sandi berhasil diubah. Silakan login kembali.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('login')]);
        }

        return redirect()->route('login')->with('success', $message);
    }
}
