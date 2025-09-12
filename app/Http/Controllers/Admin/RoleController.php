<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->paginate(20);
        $allPermissions = Permission::orderBy('name')->get();
        return view('admin.roles.index', compact('roles','allPermissions'));
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name'=>['required','string','max:60']]);
        Role::firstOrCreate(['name'=>$data['name']]);
        return back()->with('success','Role created.');
    }

    public function update(Request $r, Role $role)
    {
        $data = $r->validate([
            'name'=>['required','string','max:60'],
            'permissions'=>['array'],
            'permissions.*'=>['string','exists:permissions,name'],
        ]);

        $role->update(['name'=>$data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        return back()->with('success','Role updated.');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back()->with('success','Role deleted.');
    }
}
