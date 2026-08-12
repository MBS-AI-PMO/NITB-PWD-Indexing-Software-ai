<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            // Cache the heavy admin dashboard calculations (uses CACHE_STORE from .env)
            $cacheKey = 'dashboard:admin';

            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () {
                // Calculate total storage used across all companies
                $totalStorageBytes = 0;
                $allDocuments = Document::all();
                
                foreach ($allDocuments as $doc) {
                    if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                        $totalStorageBytes += \Illuminate\Support\Facades\Storage::disk('public')->size($doc->file_path);
                    }
                }
                
                // Calculate company-wise storage
                $companies = Company::withCount(['documents', 'users'])->get();
                $companyStorage = [];
                
                foreach ($companies as $company) {
                    $totalSize = 0;
                    $documents = Document::where('company_id', $company->id)->get();
                    
                    foreach ($documents as $doc) {
                        if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                            $totalSize += \Illuminate\Support\Facades\Storage::disk('public')->size($doc->file_path);
                        }
                    }
                    
                    $companyStorage[] = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'documents_count' => $company->documents_count,
                        'users_count' => $company->users_count,
                        'storage_bytes' => $totalSize,
                        'storage_formatted' => $this->formatBytes($totalSize),
                    ];
                }
                
                // Sort by storage size (descending)
                usort($companyStorage, function($a, $b) {
                    return $b['storage_bytes'] - $a['storage_bytes'];
                });
                
                // Get recent activity across all companies (latest 5 documents)
                $recentDocuments = Document::with(['uploader', 'category', 'company'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                $recentActivity = [];
                foreach ($recentDocuments as $doc) {
                    $fileUrl = null;
                    if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                        $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($doc->file_path);
                    }
                    
                    $recentActivity[] = [
                        'id' => $doc->id,
                        'user' => $doc->uploader ? $doc->uploader->name : 'Unknown',
                        'company' => $doc->company ? $doc->company->name : 'Unknown',
                        'action' => 'uploaded',
                        'file' => $doc->subject_title,
                        'time' => $doc->created_at->diffForHumans(),
                        'file_url' => $fileUrl,
                    ];
                }
                
                // Count active folders across all companies
                $totalFolders = \App\Models\Folder::count();
                
                // Count active companies (companies with at least one document or user)
                $activeCompanies = Company::where(function($query) {
                    $query->whereHas('documents')->orWhereHas('users');
                })->count();
                
                $stats = [
                    'total_documents' => Document::count(),
                    'total_users' => User::where('role', '!=', 'admin')->count(),
                    'total_companies' => Company::count(),
                    'active_companies' => $activeCompanies,
                    'total_folders' => $totalFolders,
                    'storage_used' => $this->formatBytes($totalStorageBytes),
                    'recent_activity' => $recentActivity,
                ];

                return [
                    'stats' => $stats,
                    'companyStorage' => $companyStorage,
                ];
            });

            $stats = $data['stats'];
            $companyStorage = $data['companyStorage'];
            
        } else {
            // Company user dashboard - cache per company
            $companyId = $user->company_id;
            $cacheKey = 'dashboard:company:'.$companyId;

            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($companyId) {
                // Calculate total storage used
                $totalStorageBytes = 0;
                $documents = Document::where('company_id', $companyId)->get();
                
                foreach ($documents as $doc) {
                    if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                        $totalStorageBytes += \Illuminate\Support\Facades\Storage::disk('public')->size($doc->file_path);
                    }
                }
                
                // Calculate storage breakdown by file type
                $storageBreakdown = [
                    'pdf' => ['bytes' => 0, 'count' => 0],
                    'image' => ['bytes' => 0, 'count' => 0],
                    'other' => ['bytes' => 0, 'count' => 0],
                ];
                
                foreach ($documents as $doc) {
                    if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                        $fileSize = \Illuminate\Support\Facades\Storage::disk('public')->size($doc->file_path);
                        $extension = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                        
                        if ($extension === 'pdf') {
                            $storageBreakdown['pdf']['bytes'] += $fileSize;
                            $storageBreakdown['pdf']['count']++;
                        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'])) {
                            $storageBreakdown['image']['bytes'] += $fileSize;
                            $storageBreakdown['image']['count']++;
                        } else {
                            $storageBreakdown['other']['bytes'] += $fileSize;
                            $storageBreakdown['other']['count']++;
                        }
                    }
                }
                
                // Format storage breakdown
                $storageBreakdownFormatted = [];
                foreach ($storageBreakdown as $type => $data) {
                    if ($data['bytes'] > 0) {
                        $storageBreakdownFormatted[] = [
                            'type' => ucfirst($type === 'image' ? 'Images & Media' : ($type === 'pdf' ? 'PDF Documents' : 'Other Files')),
                            'bytes' => $data['bytes'],
                            'formatted' => $this->formatBytes($data['bytes']),
                            'count' => $data['count'],
                            'percentage' => $totalStorageBytes > 0 ? round(($data['bytes'] / $totalStorageBytes) * 100, 1) : 0,
                        ];
                    }
                }
                
                // Get recent activity (latest 4 documents)
                $recentDocuments = Document::where('company_id', $companyId)
                    ->with(['uploader', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(4)
                    ->get();
                
                $recentActivity = [];
                foreach ($recentDocuments as $doc) {
                    $fileUrl = null;
                    if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
                        $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($doc->file_path);
                    }
                    
                    $recentActivity[] = [
                        'id' => $doc->id,
                        'user' => $doc->uploader ? $doc->uploader->name : 'Unknown',
                        'action' => 'uploaded',
                        'file' => $doc->subject_title,
                        'time' => $doc->created_at->diffForHumans(),
                        'color' => 'blue',
                        'file_path' => $doc->file_path,
                        'file_url' => $fileUrl,
                    ];
                }
                
                // Count active folders
                $activeFoldersCount = \App\Models\Folder::where('company_id', $companyId)->count();
                
                $stats = [
                    'total_documents' => Document::where('company_id', $companyId)->count(),
                    'total_users' => User::where('company_id', $companyId)->count(),
                    'total_companies' => 1,
                    'storage_used' => $this->formatBytes($totalStorageBytes),
                    'active_folders' => $activeFoldersCount,
                    'storage_breakdown' => $storageBreakdownFormatted,
                    'recent_activity' => $recentActivity,
                ];

                return [
                    'stats' => $stats,
                    'companyStorage' => [],
                ];
            });

            $stats = $data['stats'];
            $companyStorage = $data['companyStorage'];
        }

        return view('admin.dashboard', compact('stats', 'companyStorage'));
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
