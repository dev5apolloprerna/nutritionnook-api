<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.roles.list', compact('roles'));
    }

    public function create()
    {
        $modules = Module::all();
        return view('admin.roles.form', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'modules' => 'nullable|array',
            'no_permission' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);
        
        // if (!$request->has('no_permission') && empty($request->modules)) {
        //     return redirect()->back()->withErrors(['modules' => 'Please select either "No Permission" or at least one module.'])->withInput();
        // }

        if (!$request->has('no_permission') && empty($request->modules)) {
            return redirect()->back()
                ->with('error', 'Please select either "No Permission" or at least one module.')
                ->withInput();
        }

        $role = Role::create($request->only('title', 'status'));

        $noPermission = $request->has('no_permission') ? 1 : 0;

        // Check if modules are provided and iterate over them
        if (!empty($request->modules)) {
            foreach ($request->modules as $moduleId => $permissions) {
                // Insert each permission record with role_id and module_id
                Permission::create([
                    'role_id' => $role->id,  // Include the role_id
                    'module_id' => $moduleId,
                    'list_permission' => isset($permissions['list']) ? 1 : 0,
                    'create_permission' => isset($permissions['create']) ? 1 : 0,
                    'edit_permission' => isset($permissions['edit']) ? 1 : 0,
                    'delete_permission' => isset($permissions['delete']) ? 1 : 0,
                    'no_permission' => $noPermission,
                ]);
            }
        } else {
            // If no modules are selected, still create a default permission record with no_permission set to 1
            Permission::create([
                'role_id' => $role->id,
                'module_id' => null, // Or any default value
                'no_permission' => $noPermission, // Set the value of no_permission
            ]);
        }


        return redirect()->route('roles.index')->with('success', 'Role added successfully!');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        // Modules ko fetch kar rahe hain
        $modules = Module::all();

        // Permissions ko fetch kar rahe hain
        $permissions = Permission::where('role_id', $id)->get()->keyBy('module_id');

        $noPermission = Permission::where('role_id', $id)->first()->no_permission ?? 0;

        return view('admin.roles.form', compact('role', 'modules', 'permissions', 'noPermission'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Custom validation for permissions (same as store)
        // if (!$request->has('no_permission') && empty($request->modules)) {
        //     return redirect()->back()
        //         ->withErrors(['modules' => 'Please select either "No Permission" or at least one module.'])
        //         ->withInput();
        // }
        if (!$request->has('no_permission') && empty($request->modules)) {
            return redirect()->back()
                ->with('error', 'Please select either "No Permission" or at least one module.')
                ->withInput();
        }

        $role = Role::findOrFail($id);
        $role->update($request->only('title', 'status'));

        // Delete old permissions
        Permission::where('role_id', $id)->delete();

        $noPermission = $request->has('no_permission') ? 1 : 0;

        if (!empty($request->modules)) {
            foreach ($request->modules as $moduleId => $permissions) {
                Permission::create([
                    'role_id' => $role->id,
                    'module_id' => $moduleId,
                    'list_permission' => isset($permissions['list']) ? 1 : 0,
                    'create_permission' => isset($permissions['create']) ? 1 : 0,
                    'edit_permission' => isset($permissions['edit']) ? 1 : 0,
                    'delete_permission' => isset($permissions['delete']) ? 1 : 0,
                    'no_permission' => $noPermission,
                ]);
            }
        } else {
            Permission::create([
                'role_id' => $role->id,
                'module_id' => null,
                'no_permission' => $noPermission,
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
    }
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        Permission::where('role_id', $id)->delete();

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully!');
    }
}
