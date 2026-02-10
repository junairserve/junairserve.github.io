<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkCancelRequest;
use App\Http\Requests\LinkStoreRequest;
use App\Models\DevicePcbLink;
use App\Services\LinkService;
use RuntimeException;

class LinkController extends Controller
{
    public function __construct(private readonly LinkService $linkService)
    {
    }

    public function index()
    {
        $latest = DevicePcbLink::query()->latest('linked_at')->limit(20)->get();

        return view('links.index', compact('latest'));
    }

    public function store(LinkStoreRequest $request)
    {
        try {
            $this->linkService->link($request, $request->body_sn, $request->pcb_sn);
        } catch (RuntimeException $e) {
            return back()->withErrors(['link' => $e->getMessage()])->withInput();
        }

        return back()->with('status', '紐づけを登録しました');
    }

    public function cancel(LinkCancelRequest $request, DevicePcbLink $link)
    {
        try {
            $this->linkService->cancel($request, $link, $request->reason);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('status', '取消しました');
    }
}
