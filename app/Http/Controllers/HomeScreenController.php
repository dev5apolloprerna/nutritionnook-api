<?php

namespace App\Http\Controllers;

use App\Models\HomeScreen;
use Illuminate\Http\Request;

class HomeScreenController extends Controller
{
    // ✅ List
    public function index()
    {
        $items = HomeScreen::all();
        return view('admin.homescreen.list', compact('items'));
    }

    // ✅ Show Create Form
    public function create()
    {
        return view('admin.homescreen.form');
    }

    // ✅ Store
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'subtext'      => 'nullable|string',
            'button_title' => 'nullable|string|max:255',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

         if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $fileName);
            $data['image'] = 'images/' . $fileName;
        }

        HomeScreen::create($data);

        return redirect()->route('homescreen.index')->with('success', 'Record created successfully');
    }

    // ✅ Show Edit Form
    public function edit(HomeScreen $homescreen)
    {
        return view('admin.homescreen.form', compact('homescreen'));
    }

    // ✅ Update
    public function update(Request $request, HomeScreen $homescreen)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'subtext'      => 'nullable|string',
            'button_title' => 'nullable|string|max:255',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

         if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $fileName);
            $data['image'] = 'images/' . $fileName;
        }

        $homescreen->update($data);

        return redirect()->route('homescreen.index')->with('success', 'Record updated successfully');
    }

    // ✅ Delete
    public function destroy(HomeScreen $homescreen)
    {
        $homescreen->delete();
        return redirect()->route('homescreen.index')->with('success', 'Record deleted successfully');
    }
}
