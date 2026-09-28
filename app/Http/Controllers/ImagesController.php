<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImagesRequest;
use App\Http\Requests\UpdateImagesRequest;
use App\Models\Images;

class ImagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImagesRequest $request)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Display the specified resource.
     */
    public function show(Images $images)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Images $images)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateImagesRequest $request, Images $images)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Images $images)
    {
        // TODO: unrouted scaffold with no admin counterpart. Implement this action and route it, or delete this controller.
    }
}
