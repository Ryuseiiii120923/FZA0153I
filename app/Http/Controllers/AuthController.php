<?php

namespace App\Http\Controllers;

use App\Models\DefectInsp;
use App\Models\Employee;
use App\Models\Worker;
use App\Models\EmpTrace;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
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
                        Auth::login($user);
                        $request->session()->regenerate();

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
        $employeeTrace = EmpTrace::whereRaw("RIGHT(CAST(emp_Id AS VARCHAR(20)), 4) = ?", [$request->input('userid')])->first();
        $plant = null;
        if ($employeeTrace) {
            if ($employeeTrace->teamId === '4') {
                $plant = 1;
            } elseif ($employeeTrace->teamId === '7') {
                $plant = 2;
            } else {
                return back()->withErrors(['credentials' => 'Must belong to VI']);
            }
        }

        if ($user && $user->PASSWORD === $request->input('password')) {
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('gl.dashboard', ['systemname' => 'GLDashboard', 'process' => 'VI', 'plant' => $plant]);
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
