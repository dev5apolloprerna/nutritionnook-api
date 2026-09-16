<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class CustomerManagementController extends Controller
{
    // Show all users
    public function index()
    {

        $users = \DB::table('users')->where('is_admin', '!=', 1)
            ->orderBy('id', 'desc') // latest user first
            ->get();
        return view('admin.customers.list', compact('users'));
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('customers.index')->with('success', 'Customers deleted successfully.');
    }
    
    public function show($id)
    {
        $user = User::findOrFail($id);
    
        // user ke addresses fetch karo
        $addresses = \DB::table('user_addresses')
            ->where('user_id', $id)
            ->get();
    
        return view('admin.customers.show', compact('user', 'addresses'));
    }


}
