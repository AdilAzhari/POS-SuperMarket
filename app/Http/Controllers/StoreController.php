<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorestoreRequest;
use App\Http\Requests\UpdatestoreRequest;
use App\Models\Store;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Store::paginate(20));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorestoreRequest $request)
    {
        $store = Store::create($request->validated());

        return response()->json($store, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        return response()->json($store);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        return response()->json($store);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatestoreRequest $request, Store $store)
    {
        $store->update($request->validated());

        return response()->json($store->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();

        return response()->noContent();
    }
}
