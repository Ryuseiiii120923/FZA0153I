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
        $request->validate([
            'userid' => 'required|integer',
            'password' => 'required|string',
        ]); 
        $user = Employee::where('社員CD', $request->input('userid'))->first();
        if($user && $user->PASSWORD === $request->input('password')){
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('post.add');
        }

        // $validated = $request -> validate([
        //     'userid' => 'required|integer',
        //     'password' => 'required|string',
        // ]);
        // dd($validated);

        // if(Auth::attempt($validated)) {
        //     $request->session()->regenerate();

        //     return redirect()->route('home.index');
        // }
        throw ValidationException::withMessages([
            'credentials' => 'Sorry, incorrect credentiaks'
        ]);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
