<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Upload::with(['revisionHistories' => function ($q) {
                $q->orderBy('created_at');
            }])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('folder')) {
            $query->where('subfolder', $request->folder);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('filename', 'like', "%{$search}%");
        }

        $uploads = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $folders = Upload::where('user_id', $user->id)
            ->whereNotNull('subfolder')
            ->where('subfolder', '!=', '(folder utama)')
            ->select('subfolder')
            ->distinct()
            ->orderBy('subfolder')
            ->pluck('subfolder');

        $folders = collect(['(folder utama)'])->merge($folders);

        $statusLabelMap = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'revision' => 'Butuh Revisi',
        ];

        return view('riwayat.index', compact('uploads', 'folders', 'statusLabelMap'));
    }
}
