<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $parentId = $request->get('parent_id');
        $folders = \App\Models\Folder::where('parent_id', $parentId)->withCount('children')->get();
        return view('admin.files.index', compact('folders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
        ]);

        \App\Models\Folder::create($validated);

        return redirect()->back()->with('success', 'Folder created successfully');
    }

    public function update(Request $request, string $id)
    {
        $folder = \App\Models\Folder::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $folder->update($validated);

        return redirect()->back()->with('success', 'Folder updated successfully');
    }

    public function destroy(string $id)
    {
        $folder = \App\Models\Folder::findOrFail($id);
        $folder->delete();

        return redirect()->back()->with('success', 'Folder deleted successfully');
    }
}
