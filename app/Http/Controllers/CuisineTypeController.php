<?php

namespace App\Http\Controllers;

use App\Models\CuisineType;
use Illuminate\Http\Request;

class CuisineTypeController extends Controller
{
    public function index()
    {
        $cuisineTypes = CuisineType::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.cuisine_types.list', compact('cuisineTypes'));
    }

    public function create()
    {
        return view('admin.cuisine_types.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $data = $request->only(['title', 'status']);

        if ($request->hasFile('image')) {
            $filename = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('images'), $filename);
            $data['image'] = 'public/images/'.$filename;
        }
    
        CuisineType::create($data);

    
        return redirect()->route('cuisine-types.index')->with('success', 'Cuisine Type added successfully!');
    }

    public function edit($id)
    {
        $cuisineType = CuisineType::findOrFail($id);
        return view('admin.cuisine_types.form', compact('cuisineType'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        // $cuisineType = CuisineType::findOrFail($id);
        // $cuisineType->update($request->only(['title', 'status']));
        
        $cuisineType = CuisineType::findOrFail($id);
        $data = $request->only(['title', 'status']);
    
        if ($request->hasFile('image')) {
            $filename = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('images'), $filename);
            $data['image'] = 'public/images/'.$filename;
        }
    
        $cuisineType->update($data);
        
        return redirect()->route('cuisine-types.index')->with('success', 'Cuisine Type updated successfully!');
    }

    public function destroy($id)
    {
        $cuisineType = CuisineType::findOrFail($id);
        $cuisineType->delete();
        return redirect()->route('cuisine-types.index')->with('success', 'Cuisine Type deleted successfully!');
    }
}
