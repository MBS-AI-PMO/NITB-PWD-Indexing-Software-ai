<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = \App\Models\Category::all();
        return view('admin.files.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:1|unique:categories',
            'name' => 'required|string|max:255',
            'retention_description' => 'nullable|string',
        ]);
        $validated['company_id'] = auth()->user()->company_id;
        \App\Models\Category::create($validated);

        return redirect()->back()->with('success', 'Category created successfully');
    }

    public function update(Request $request, string $id)
    {
        $category = \App\Models\Category::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|string|max:1|unique:categories,code,' . $id,
            'name' => 'required|string|max:255',
            'retention_description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated successfully');
    }

    public function destroy(string $id)
    {
        $category = \App\Models\Category::findOrFail($id);
        if ($category->company_id !== auth()->user()->company_id) {
            return redirect()->back()->with('error', 'You are not authorized to delete this category');
        }
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully');
    }
}
