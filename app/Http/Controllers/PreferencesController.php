<?php

namespace App\Http\Controllers;

use App\Models\Preference;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    public function index()
    {
        $preferences = Preference::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.preferences.list', compact('preferences'));
    }

    public function create()
    {
        return view('admin.preferences.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        Preference::create($request->only(['name', 'status']));
        return redirect()->route('preferences.index')->with('success', 'Preference added successfully!');
    }

    public function edit($id)
    {
        $preference = Preference::findOrFail($id);
        return view('admin.preferences.form', compact('preference'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $preference = Preference::findOrFail($id);
        $preference->update($request->only(['name', 'status']));
        return redirect()->route('preferences.index')->with('success', 'Preference updated successfully!');
    }

    public function destroy($id)
    {
        $preference = Preference::findOrFail($id);
        $preference->delete();
        return redirect()->route('preferences.index')->with('success', 'Preference deleted successfully!');
    }
}
