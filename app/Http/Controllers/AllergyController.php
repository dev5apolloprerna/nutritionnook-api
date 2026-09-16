<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use Illuminate\Http\Request;

class AllergyController extends Controller
{
    public function list()
    {
        $allergies = Allergy::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.allergies.list', compact('allergies'));
    }

    public function create()
    {
        return view('admin.allergies.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->only(['title', 'status']);

        Allergy::create($data);
        return redirect()->route('allergies.index')->with('success', 'Allergy added successfully!');
    }

    public function edit($id)
    {
        $allergy = Allergy::findOrFail($id);
        return view('admin.allergies.form', compact('allergy'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive'
        ]);

        $allergy = Allergy::findOrFail($id);
        $data = $request->only(['title', 'status']);

        $allergy->update($data);

        return redirect()->route('allergies.index')->with('success', 'Allergy updated successfully!');
    }

    public function destroy($id)
    {
        $allergy = Allergy::findOrFail($id);
        $allergy->delete();
        return redirect()->route('allergies.index')->with('success', 'Allergy deleted successfully!');
    }
}