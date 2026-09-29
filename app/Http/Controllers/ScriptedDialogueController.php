<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScriptedDialogueRequest;
use App\Http\Requests\UpdateScriptedDialogueRequest;
use App\Models\ScriptedDialogue;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Every action is authorized through `ScriptedDialoguePolicy` by an `#[Authorize]`
 * attribute, which the router runs as `can:` middleware once the action is
 * routed, so each action is guarded before its body is written.
 */
class ScriptedDialogueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', ScriptedDialogue::class)]
    public function index()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', ScriptedDialogue::class)]
    public function create()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', ScriptedDialogue::class)]
    public function store(StoreScriptedDialogueRequest $request)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    #[Authorize('view', 'scripted_dialogue')]
    public function show(ScriptedDialogue $scriptedDialogue)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'scripted_dialogue')]
    public function edit(ScriptedDialogue $scriptedDialogue)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'scripted_dialogue')]
    public function update(UpdateScriptedDialogueRequest $request, ScriptedDialogue $scriptedDialogue)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'scripted_dialogue')]
    public function destroy(ScriptedDialogue $scriptedDialogue)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedDialogueController. Implement a student-facing action and route it, or delete this controller.
    }
}
