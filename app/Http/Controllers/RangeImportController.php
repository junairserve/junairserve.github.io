<?php

namespace App\Http\Controllers;

use App\Http\Requests\RangeImportRequest;
use App\Services\AuditLogService;
use App\Services\RangeImportService;
use RuntimeException;

class RangeImportController extends Controller
{
    public function __construct(
        private readonly RangeImportService $rangeImportService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function index()
    {
        return view('ranges.index');
    }

    public function store(RangeImportRequest $request)
    {
        try {
            $count = $this->rangeImportService->import(
                $request->string('type')->toString(),
                $request->string('start_sn')->toString(),
                $request->string('end_sn')->toString(),
                $request->string('lot_name')->toString() ?: null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['range' => $e->getMessage()])->withInput();
        }

        $this->auditLogService->write(
            $request,
            'range.import',
            'serial_pool',
            null,
            null,
            [
                'type' => $request->type,
                'start_sn' => $request->start_sn,
                'end_sn' => $request->end_sn,
                'count' => $count,
            ],
        );

        return back()->with('status', "{$count}件を受入しました");
    }
}
