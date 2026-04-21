<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use function Symfony\Component\Clock\now;

class AuthController extends Controller
{
    public function showLogin()
    {
        if(session()->has('super_admin_authorized')){
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if($request->username === env('SUPER_ADMIN_USER') && $request->password === env('SUPER_ADMIN_PASSWORD')){
            session()->put('super_admin_authorized', true);
            session()->put('super_admin_login_time', now());

            return redirect()->route('superadmin.dashboard')->with('success', 'Master Access Granted. Welcome, Super Admin!');
        }

        return back()->with('error', 'Invalid credentials. Please try again.');
    }

    public function logout()
    {
        session()->forget(['super_admin_authorized', 'super_admin_login_time']);
        return redirect()->route('superadmin.login')->with('success', 'You have been logged out successfully.');
    }
}
