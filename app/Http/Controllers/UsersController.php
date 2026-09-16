<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function destroyAddress(UserAddress $address)
{
    try {
        $address->delete();
        return redirect()->back()->with('success', 'Address deleted successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Failed to delete address.');
    }
}
    // Show all users
    public function index()
    {
        $users = User::where('is_admin', '!=', 1)
            ->orderBy('id', 'desc') // latest user first
            ->get();
        return view('admin.users.list', compact('users'));
    }

    public function create()
    {
        // $roles = Role::all();
        $roles = Role::all()->reject(function ($role) {
            return strtolower($role->title) === 'chef' || strtolower($role->title) === 'chefs';
        });

        return view('admin.users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone_number' => 'required|numeric|unique:users,phone_number',
            'password' => 'required|string|min:8',
            // 'address' => 'required|string',
            'gender' => 'required|in:male,female,other,prefer not to disclose',
            'dob' => 'required|date',
            'status' => 'required|in:active,inactive',
            'user_role' => 'required|exists:roles,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',

        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
        } else {
            $imageName = null;
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'password' => Hash::make($validatedData['password']),
            // 'address' => $validatedData['address'],
            'gender' => $validatedData['gender'],
            'dob' => $validatedData['dob'],
            'status' => $validatedData['status'],
            'user_role' => $validatedData['user_role'],
            'image' => $imageName,
        ]);

        // if ($request->has('cuisine_types')) {
        //     $user->cuisineTypes()->sync($request->input('cuisine_types'));
        // }

        // if ($request->has('preferences')) {
        //     $user->preferences()->sync($request->input('preferences'));
        // }

        return redirect()->route('users.index')->with('success', 'User added successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        // $roles = Role::all();
        $roles = Role::all()->reject(function ($role) {
            return strtolower($role->title) === 'chef' || strtolower($role->title) === 'chefs';
        });
        return view('admin.users.form', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['required', 'numeric', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            // 'address' => 'required|string',
            'gender' => 'required|in:male,female,other,prefer not to disclose',
            'dob' => 'required|date',
            'status' => 'required|in:active,inactive',
            'user_role' => 'required|exists:roles,id',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        $updateData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            // 'address' => $validatedData['address'],
            'status' => $validatedData['status'],
            'user_role' => $validatedData['user_role'],
            'gender' => $validatedData['gender'],
            'dob' => $validatedData['dob'],
        ];

        // Only update password if provided
        if (!empty($validatedData['password'])) {
            $updateData['password'] = Hash::make($validatedData['password']);
        }

        // Handle image update
        if ($request->hasFile('image')) {
            if ($user->image && file_exists(public_path('images/' . $user->image))) {
                unlink(public_path('images/' . $user->image));
            }

            $image     = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);

            $updateData['image'] = $imageName;
        }

        $user->update($updateData);

        // $user->cuisineTypes()->sync($request->input('cuisine_types', []));
        // $user->preferences()->sync($request->input('preferences', []));

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
    
public function show($id)
{
    $user = User::findOrFail($id);

    // user ke addresses fetch karo
    $addresses = \DB::table('user_addresses')
        ->where('user_id', $id)
        ->get();

    return view('admin.users.show', compact('user', 'addresses'));
}


}
