<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class TestSalesRepController extends Controller
{
    public function testLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'logged_in' => true,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_type' => $user->user_type,
                'email' => $user->email,
                'banned' => isset($user->banned) ? $user->banned : 'field not exists'
            ]);
        } else {
            return response()->json(['logged_in' => false]);
        }
    }

    public function testDashboardAccess()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Sales Rep dashboard is accessible!',
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'user_type' => Auth::user()->user_type
            ] : null
        ]);
    }
}