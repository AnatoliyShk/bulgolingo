<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBotRequest;
use App\Http\Requests\UpdateBotRequest;
use App\Models\Bot;

class BotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBotRequest $request)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    public function show(Bot $bot)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bot $bot)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBotRequest $request, Bot $bot)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bot $bot)
    {
        // TODO: unrouted scaffold; the admin CRUD lives in Admin\BotController. Implement a student-facing action and route it, or delete this controller.
    }
}
