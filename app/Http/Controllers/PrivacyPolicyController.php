<?php

namespace App\Http\Controllers;

use App\Printer;
use Datatables;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function privacy()
    {
        return view('PrivacyPolicy.index');
    }
}