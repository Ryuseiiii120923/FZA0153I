<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
public function login(Request $request)
{
    // --- QR login ---
    if ($request->has('qr')) {
        $qrData = $request->input('qr');
        if (is_string($qrData)) {
            $qrData = json_decode($qrData, true);
        }

        if (isset($qrData['userid'], $qrData['password'])) {
            $user = Employee::where('社員CD', $qrData['userid'])->first();
            if ($user && $user->PASSWORD === $qrData['password']) {
                Auth::login($user);
                $request->session()->regenerate();

                // Role-based redirect
                if ($qrData['role'] === 'GL') {
                    return redirect()->route('gl.dashboard'); 
                } else {
                    return redirect()->route('selector');
                }
            } else {
                return back()->withErrors(['credentials' => 'Invalid QR code login']);
            }
        }
    }
    

    // --- Manual login ---
    $request->validate([
        'userid' => 'required|integer',
        'password' => 'required|string',
    ]);

    $user = Employee::where('社員CD', $request->input('userid'))->first();

    if($user && $user->PASSWORD === $request->input('password')){
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('selector');
    }

    return back()->withErrors(['credentials' => 'Incorrect credentials']);
}


    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
