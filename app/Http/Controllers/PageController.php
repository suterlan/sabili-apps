<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return view('pages.index');
    }
    public function about()
    {
        return view('pages.about');
    }
    public function terms()
    {
        return view('pages.terms');
    }
    public function privacy()
    {
        return view('pages.privacy');
    }
    public function refund()
    {
        return view('pages.refund');
    }
}
