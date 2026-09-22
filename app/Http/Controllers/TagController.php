<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function list()
    {
        $tags = Tag::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.tags.list', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Category::create($request->only(['title', 'status']));
        $data = $request->only(['title', 'status']);

            // if ($request->hasFile('image')) {
            //     $imageName = time() . '.' . $request->image->extension();
            //     $request->image->move(public_path('images'), $imageName);
            //     $data['image'] = $imageName;
            // }

        Tag::create($data);
        return redirect()->route('tags.index')->with('success', 'Tag added successfully!');
    }

    public function edit($id)
    {
        $tag = Tag::findOrFail($id);
        return view('admin.tags.form', compact('tag'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive'
        ]);

        // $category = Category::findOrFail($id);
        // $category->update($request->only(['title', 'status']));
        
        $tag = Tag::findOrFail($id);
        $data = $request->only(['title', 'status']);
    
        // if ($request->hasFile('image')) {
        //     // Purani image delete (optional)
        //     if ($category->image && file_exists(public_path('images/' . $category->image))) {
        //         unlink(public_path('images/' . $category->image));
        //     }
    
        //     $imageName = time() . '.' . $request->image->extension();
        //     $request->image->move(public_path('images'), $imageName);
        //     $data['image'] = $imageName;
        // }

        $tag->update($data);

        return redirect()->route('tags.index')->with('success', 'Tag updated successfully!');
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
        return redirect()->route('tags.index')->with('success', 'Tag deleted successfully!');
    }
}
