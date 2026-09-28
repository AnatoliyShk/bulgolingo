<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScriptedLineRequest;
use App\Http\Requests\UpdateScriptedLineRequest;
use App\Models\ScriptedLine;

class ScriptedLineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreScriptedLineRequest $request)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    public function show(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateScriptedLineRequest $request, ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScriptedLine $scriptedLine)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\ScriptedLineController. Implement a student-facing action and route it, or delete this controller.
    }
}
