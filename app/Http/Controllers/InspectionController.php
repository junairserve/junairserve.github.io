<?php

namespace App\Http\Controllers;

use App\Http\Requests\InspectionStoreRequest;
use App\Models\Inspection;
use App\Services\InspectionService;
use RuntimeException;

class InspectionController extends Controller
{
    public function __construct(private readonly InspectionService $inspectionService)
    {
    }

    public function index()
    {
        $inspections = Inspection::query()->latest()->paginate(30);

        return view('inspections.index', compact('inspections'));
    }

    public function store(InspectionStoreRequest $request)
    {
        try {
            $this->inspectionService->create($request, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withErrors(['inspection' => $e->getMessage()])->withInput();
        }

        return back()->with('status', '検査記録を登録しました');
    }
}
