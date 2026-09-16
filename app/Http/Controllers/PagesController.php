<?php

namespace App\Http\Controllers;

use App\Models\page;
use Illuminate\Http\Request;

class PagesController extends Controller
{

    // Show the list of blogs
    public function index()
    {
        $pages = page::all();
        return view('admin.pages.list', compact('pages'));
    }

    // Show form for creating a new blog
    public function create()
    {
        return view('admin.pages.form');
    }

    // Show form for editing an existing blog
    public function edit($id)
    {
        $page = page::findOrFail($id);
        return view('admin.pages.form', compact('page'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'heading' => 'nullable|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
            'status' => 'required|in:0,1',  // Use 0 and 1 for status validation
        ]);

        // Ensure 'status' is converted to integer if passed as string
        $data['status'] = (int)$data['status'];

        // Handle image upload
       if ($request->hasFile('image')) {
            $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->move(public_path('images'), $fileName); 
            $data['image'] = 'public/images/' . $fileName; // <-- ab DB me full relative path save hoga
        }


        // Create a new blog post with the validated data
        page::create($data);

        return redirect()->route('pages.index')->with('success', 'Page added successfully!');
    }




    // Update an existing blog
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'heading' => 'nullable|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
            'status' => 'required|in:0,1',
        ]);

        $data['status'] = (int)$data['status'];

        $page = page::findOrFail($id);

        if ($request->hasFile('image')) {
            $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->move(public_path('images'), $fileName); 
            $data['image'] = 'public/images/' . $fileName; // <-- ab DB me full relative path save hoga
        }

        $page->update($data);

        return redirect()->route('pages.index')->with('success', 'Page updated successfully!');
    }


    // Delete a blog
    public function destroy($id)
    {
        $page = page::findOrFail($id);
        $page->delete();

        return redirect()->route('pages.index')->with('success', 'Page deleted successfully!');
    }
}
