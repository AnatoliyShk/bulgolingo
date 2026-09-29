<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLexemaRequest;
use App\Http\Requests\UpdateLexemaRequest;
use App\Models\Lexema;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Every action is authorized through `LexemaPolicy` by an `#[Authorize]`
 * attribute, which the router runs as `can:` middleware once the action is
 * routed, so each action is guarded before its body is written.
 */
class LexemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Lexema::class)]
    public function index()
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Lexema::class)]
    public function create()
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Lexema::class)]
    public function store(StoreLexemaRequest $request)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    #[Authorize('view', 'lexema')]
    public function show(Lexema $lexema)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'lexema')]
    public function edit(Lexema $lexema)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'lexema')]
    public function update(UpdateLexemaRequest $request, Lexema $lexema)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'lexema')]
    public function destroy(Lexema $lexema)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }
}
