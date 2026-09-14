<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StoreMedicineCategoryRequest;
use App\Http\Resources\MedicineCategoryResource;
use App\Models\Medicine;
use App\Models\MedicineCategory;

class MedicineCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Medicine::class);

        return $this->success(MedicineCategoryResource::collection(MedicineCategory::orderBy('name')->get()));
    }

    public function store(StoreMedicineCategoryRequest $request)
    {
        $category = MedicineCategory::create($request->validated());

        return $this->success(new MedicineCategoryResource($category), 'Medicine category created successfully', 201);
    }
}
