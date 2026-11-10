<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // If already signed in on either system, skip the picker and go
        // straight to the dashboard that account actually has access to.
        if (Auth::guard('web')->check()) {
            return redirect()->route(Auth::guard('web')->user()->dashboardRoute());
        }
        if (Auth::guard('accounts')->check()) {
            return redirect()->route(Auth::guard('accounts')->user()->dashboardRoute());
        }

        return view('landing');
    }
}
