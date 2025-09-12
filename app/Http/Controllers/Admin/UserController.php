<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(20);
        $roles = Role::orderBy('name')->get();
        return view('admin.users.index', compact('users','roles'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','unique:users,email'],
            'password'=>['required','string','min:6'],
            'roles'=>['array'],
            'roles.*'=>['string','exists:roles,name'],
        ]);

        $u = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
        ]);
        $u->syncRoles($data['roles'] ?? []);
        return back()->with('success','User created.');
    }

    public function update(Request $r, User $user)
    {
        $data = $r->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','unique:users,email,'.$user->id],
            'password'=>['nullable','string','min:6'],
            'roles'=>['array'],
            'roles.*'=>['string','exists:roles,name'],
        ]);

        $user->update([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>!empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);
        $user->syncRoles($data['roles'] ?? []);
        return back()->with('success','User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success','User deleted.');
    }
}
