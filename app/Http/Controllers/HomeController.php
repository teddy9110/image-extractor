<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;

class HomeController extends Controller
{
    public function list(Request $request)
    {
        $photos = Photo::all();
        return view('home', ['photos' => $photos]);
    }

}