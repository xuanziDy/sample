<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Userscontroller extends Controller
{
    public function create()
    {
        return view('users.create');
    }
}
