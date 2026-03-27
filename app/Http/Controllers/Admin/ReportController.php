<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::latest();

        if ($request->filled('status')) {
            $query->where('admin_status', $request->status);
        }

        if ($request->filled('cnn')) {
            $query->where('cnn_status', $request->cnn);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%");
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => Report::count(),
            'pending'  => Report::where('admin_status', 'pending')->count(),
            'approved' => Report::where('admin_status', 'approved')->count(),
            'rejected' => Report::where('admin_status', 'rejected')->count(),
            'valid'    => Report::where('cnn_status', 'valid')->count(),
            'invalid'  => Report::where('cnn_status', 'invalid')->count(),
        ];

        return view('admin.dashboard', compact('reports', 'stats'));
    }

    public function show(Report $report)
    {
        return view('admin.show', compact('report'));
    }

    public function approve(Request $request, Report $report)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $report->update([
            'admin_status' => 'approved',
            'admin_note'   => $request->admin_note,
        ]);

        return back()->with('success', 'Laporan disetujui.');
    }

    public function reject(Request $request, Report $report)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $report->update([
            'admin_status' => 'rejected',
            'admin_note'   => $request->admin_note,
        ]);

        return back()->with('success', 'Laporan ditolak.');
    }

    public function map()
    {
        $reports = Report::where('admin_status', 'approved')
            ->select('id', 'name', 'description', 'address', 'latitude', 'longitude', 'photo_path', 'cnn_confidence', 'created_at')
            ->get();

        return view('admin.map', compact('reports'));
    }
}
