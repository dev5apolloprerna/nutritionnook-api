<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function list()
    {
        $categories = Category::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.categories.list', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
            'image'  => 'required|image|mimes:jpg,jpeg,png',
        ], [
            'image.required' => 'Please upload a category image or icon.',
            'image.image'    => 'The file must be a valid image.',
            'image.mimes'    => 'Only JPG, JPEG, and PNG formats are allowed.',
        ]);

        // Category::create($request->only(['title', 'status']));
        $data = $request->only(['title', 'status']);

            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->extension();
                $request->image->move(public_path('images'), $imageName);
                $data['image'] = $imageName;
            }

        Category::create($data);
        return redirect()->route('categories.index')->with('success', 'Category added successfully!');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
            'image'  => 'nullable|image|mimes:jpg,jpeg,png',
        ], [
            'image.required' => 'Please upload a category image or icon.',
            'image.image'    => 'The file must be a valid image.',
            'image.mimes'    => 'Only JPG, JPEG, and PNG formats are allowed.',
        ]);

        // $category = Category::findOrFail($id);
        // $category->update($request->only(['title', 'status']));
        
        $category = Category::findOrFail($id);
        $data = $request->only(['title', 'status']);
    
        if ($request->hasFile('image')) {
            // Purani image delete (optional)
            if ($category->image && file_exists(public_path('images/' . $category->image))) {
                unlink(public_path('images/' . $category->image));
            }
    
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $data['image'] = $imageName;
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully!');
    }
}
