<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Worker;
use App\Models\EmpTrace;
use App\Services\Operator\PlantResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, PlantResolver $plantResolver)
    {

        if ($request->has('qr')) {
            $qrData = $request->input('qr');
            if (is_string($qrData)) {
                $qrData = json_decode($qrData, true);
            }

            if (isset($qrData['userid'], $qrData['password'])) {
                if ($qrData['role'] === 'GL') {
                    $user = Employee::where('社員CD', $qrData['userid'])->first();
                    if ($user && $user->PASSWORD === $qrData['password']) {
                        $plant = $plantResolver->forUserId($qrData['userid']);

                        if ($plant === null) {
                            return back()->withErrors(['credentials' => 'Must belong to VI']);
                        }
                        Auth::login($user);
                        $request->session()->regenerate();
                        session(['plant' => $plant]);
                        return redirect()->route('gl.dashboard', ['systemname' => 'GLDashboard']);
                    } else {
                        return back()->withErrors(['credentials' => 'Invalid QR code login']);
                    }
                } else {
                    $user = Worker::where('作業員CD', $qrData['userid'])->first();
                    if ($user) {
                        $pass = (int)$user->社員CD . $user->RECNO;
                        if ((string)$pass === $qrData['password']) {
                            Log::info('QR password matched', ['user_id' => $user->作業員CD]);

                            try {
                                Auth::guard('worker')->login($user);
                                Log::info('Worker logged in', ['user_id' => $user->作業員CD]);

                                $request->session()->regenerate();
                                Log::info('Session regenerated');
                                session(['process' => $qrData['process']]);

                                return redirect()->route('prencode', ['systemname' => 'ProcessRecord']);
                            } catch (\Throwable $e) {
                                Log::error('Worker login failed', ['message' => $e->getMessage()]);
                                dd('Error:', $e->getMessage(), get_class($user));
                            }
                        }
                    } else {
                        return back()->withErrors(['credentials' => 'User not found']);
                    }
                }
            }
        }


        // --- Manual login ---
        $request->validate([
            'userid' => 'required|integer',
            'password' => 'required|string',
        ]);

        $user = Employee::where('社員CD', $request->input('userid'))->first();
        $plant = $plantResolver->forUserId($request->input('userid'));

        if ($plant === null) {
            return back()->withErrors(['credentials' => 'Must belong to VI']);
        }

        if ($user && $user->PASSWORD === $request->input('password')) {
            Auth::login($user);
            $request->session()->regenerate();
            session(['plant' => $plant]);
            return redirect()->route('gl.dashboard');
        }
        return back()->withErrors(['credentials' => 'Incorrect credentials']);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
