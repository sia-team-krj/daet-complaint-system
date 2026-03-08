<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home()
    {
        if (auth()->check()) {
            return view("pages.home.dashboard");
        }

        return view("pages.home.guest");
    }
}
