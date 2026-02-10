<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DevicePcbLink;
use App\Models\Inspection;
use App\Models\SerialPool;
use Illuminate\Http\Request;

class SerialController extends Controller
{
    public function index(Request $request)
    {
        $statuses = $request->input('statuses', []);
        $q = $request->string('q')->toString();

        $query = SerialPool::query()
            ->select('serial_pool.*')
            ->leftJoin('device_pcb_links as dpl', function ($join): void {
                $join->on('serial_pool.sn', '=', 'dpl.body_sn')
                    ->whereNull('dpl.unlinked_at');
            })
            ->addSelect('dpl.pcb_sn')
            ->when($statuses !== [], fn ($x) => $x->whereIn('serial_pool.status', $statuses))
            ->when($q !== '', function ($x) use ($q): void {
                $x->where(function ($sub) use ($q): void {
                    $sub->where('serial_pool.sn', 'like', "%{$q}%")
                        ->orWhere('dpl.pcb_sn', 'like', "%{$q}%");
                });
            })
            ->where('serial_pool.type', 'BODY')
            ->orderByDesc('serial_pool.updated_at');

        $rows = $query->paginate(50)->withQueryString();

        $counts = [
            'UNUSED' => SerialPool::where('type', 'BODY')->where('status', 'UNUSED')->count(),
            'LINKED' => SerialPool::where('type', 'BODY')->where('status', 'LINKED')->count(),
            'INSPECTED' => SerialPool::where('type', 'BODY')->where('status', 'INSPECTED')->count(),
        ];

        return view('serials.index', compact('rows', 'counts', 'statuses', 'q'));
    }

    public function show(string $sn)
    {
        $serial = SerialPool::query()->where('type', 'BODY')->where('sn', $sn)->firstOrFail();
        $links = DevicePcbLink::query()->where('body_sn', $sn)->orderByDesc('linked_at')->get();
        $inspections = Inspection::query()->where('body_sn', $sn)->orderByDesc('created_at')->get();
        $audits = AuditLog::query()->where('entity_id', $sn)->orWhere('after_json', 'like', "%{$sn}%")->latest()->limit(100)->get();

        return view('serials.show', compact('serial', 'links', 'inspections', 'audits'));
    }
}
