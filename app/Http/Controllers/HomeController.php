<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('pages.prencode');
    }
    public function prencode()
{
    $systemname = request()->input('systemname'); // fetch from URL
    $backUrl = route('home.index');

    // Pass both to the view
    return view('pages.prencode', compact('backUrl', 'systemname'));
}
    public function selector()
    {
        return view('pages.selector');
    }

    public function operatordash()
    {
        return view('pages.operatordash');
    }

    public function Main(){
        return view('crud.add');
    }

    public function gldash(){
        return view('pages.gldashboard');
    }
}
