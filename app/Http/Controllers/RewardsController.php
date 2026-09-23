<?php

namespace App\Http\Controllers;

class RewardsController extends Controller
{
    public function index()
    {
        return view("pages.rewards.index");
    }
}
