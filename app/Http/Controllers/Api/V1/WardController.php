<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Beds\StoreWardRequest;
use App\Http\Resources\WardResource;
use App\Models\Ward;

class WardController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Ward::class);

        return $this->success(WardResource::collection(Ward::with('rooms.beds')->orderBy('name')->get()));
    }

    public function store(StoreWardRequest $request)
    {
        $ward = Ward::create($request->validated());

        return $this->success(new WardResource($ward), 'Ward created successfully', 201);
    }
}
