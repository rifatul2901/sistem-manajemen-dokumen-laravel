<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
            'nama'     => 'required|string|max:255',
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            'role'     => 'required|in:admin,user',
        ]);

        User::create([
            'username' => $request->username,
            'nama'     => $request->nama,
            'email'    => $request->username . '@sistemdokumen.test',
            'password' => $request->password,
            'role'     => $request->role,
        ]);

        return back()->with('success', "User '{$request->nama}' berhasil ditambahkan.");
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'password'      => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->password = $request->password;
        $user->save();

        return back()->with('success', "Password @{$user->username} berhasil direset.");
    }
}
