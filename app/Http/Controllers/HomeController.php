<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home()
    {
    if (auth()->check()) {
        return redirect()->route('dashboard'); 
    }
       return view("pages.home.guest");
    }
}
