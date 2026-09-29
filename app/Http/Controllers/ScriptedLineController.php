<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScriptedLineRequest;
use App\Http\Requests\UpdateScriptedLineRequest;
use App\Models\ScriptedLine;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Every action is authorized through `ScriptedLinePolicy` by an `#[Authorize]`
 * attribute, which the router runs as `can:` middleware once the action is
 * routed, so each action is guarded before its body is written.
 */
class ScriptedLineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', ScriptedLine::class)]
    public function index()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', ScriptedLine::class)]
    public function create()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', ScriptedLine::class)]
    public function store(StoreScriptedLineRequest $request)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    #[Authorize('view', 'scripted_line')]
    public function show(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'scripted_line')]
    public function edit(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'scripted_line')]
    public function update(UpdateScriptedLineRequest $request, ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'scripted_line')]
    public function destroy(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }
}
