<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\CnnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(private CnnService $cnn) {}

    public function create()
    {
        return view('report.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'description' => 'required|string|max:2000',
            'address'     => 'nullable|string|max:255',
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'photo'       => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Store photo
        $path = $request->file('photo')->store('reports', 'public');
        $absolutePath = Storage::disk('public')->path($path);

        // CNN inference
        $cnnResult = $this->cnn->predict($absolutePath);

        // Save report
        $report = Report::create([
            'name'           => $validated['name'] ?? 'Anonim',
            'phone'          => $validated['phone'] ?? null,
            'description'    => $validated['description'],
            'address'        => $validated['address'] ?? null,
            'latitude'       => $validated['latitude'],
            'longitude'      => $validated['longitude'],
            'photo_path'     => $path,
            'cnn_status'     => $cnnResult['status'],
            'cnn_confidence' => $cnnResult['confidence'],
            'admin_status'   => 'pending',
        ]);

        return redirect()->route('report.success', $report->id);
    }

    public function success(Report $report)
    {
        return view('report.success', compact('report'));
    }
}
