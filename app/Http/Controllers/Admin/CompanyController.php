<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = \App\Models\Company::with(['adminUser'])->withCount(['documents', 'folders'])->get();
        return view('admin.files.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'description' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);

        // Create company
        $company = \App\Models\Company::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Create admin user for company
        \App\Models\User::create([
            'name' => $validated['name'] . ' Admin',
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => 'company',
            'company_id' => $company->id,
        ]);

        return redirect()->back()->with('success', 'Wing created successfully');
    }

    public function update(Request $request, string $id)
    {
        $company = \App\Models\Company::with('adminUser')->findOrFail($id);
        
        $adminUserId = $company->adminUser ? $company->adminUser->id : null;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $adminUserId,
            'description' => 'nullable|string',
            'password' => 'nullable|string|min:6',
        ]);

        // Update company
        $company->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Update or create admin user
        $adminUser = $company->adminUser;
        if ($adminUser) {
            // Update existing admin user
            $adminUser->update([
                'name' => $validated['name'] . ' Admin',
                'email' => $validated['email'],
                'password' => !empty($validated['password']) 
                    ? \Illuminate\Support\Facades\Hash::make($validated['password']) 
                    : $adminUser->password,
            ]);
        } else {
            // Create new admin user if doesn't exist
            \App\Models\User::create([
                'name' => $validated['name'] . ' Admin',
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password'] ?? 'password'),
                'role' => 'company',
                'company_id' => $company->id,
            ]);
        }

        return redirect()->back()->with('success', 'Wing updated successfully');
    }

    public function toggleStatus(string $id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->is_active = !($company->is_active ?? true);
        $company->save();

        return redirect()->back()->with('success', 'Wing status updated successfully');
    }

    public function loginAs(string $id)
    {
        $company = \App\Models\Company::with('adminUser')->findOrFail($id);
        
        if (!$company->adminUser) {
            return redirect()->back()->with('error', 'Wing admin user not found');
        }

        // Store original admin user ID in session
        $originalAdmin = \Illuminate\Support\Facades\Auth::user();
        if ($originalAdmin && $originalAdmin->role === 'admin') {
            session(['original_admin_id' => $originalAdmin->id]);
        }

        \Illuminate\Support\Facades\Auth::login($company->adminUser);
        
        return redirect()->route('admin.dashboard')->with('success', 'Logged in as ' . $company->name);
    }

    public function backToAdmin()
    {
        $originalAdminId = session('original_admin_id');
        
        if (!$originalAdminId) {
            return redirect()->route('admin.dashboard')->with('error', 'No admin session found');
        }

        $originalAdmin = \App\Models\User::find($originalAdminId);
        
        if (!$originalAdmin || $originalAdmin->role !== 'admin') {
            session()->forget('original_admin_id');
            return redirect()->route('admin.dashboard')->with('error', 'Admin user not found');
        }

        \Illuminate\Support\Facades\Auth::login($originalAdmin);
        session()->forget('original_admin_id');
        
        return redirect()->route('admin.dashboard')->with('success', 'Switched back to admin account');
    }

    public function destroy(string $id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->delete();

        return redirect()->back()->with('success', 'Company deleted successfully');
    }
}
