<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('crud.add');
    }
    public function add()
    {
        $backUrl = route('home.index');
        return view('crud.add', compact('backUrl'));
    }
    public function delete()
    {
        return view('crud.delete');
    }
    public function update()
    {
        return view('crud.update');
    }
}
