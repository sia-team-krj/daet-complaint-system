<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function sample()
    {
        return view("pages.sample");
    }
}
