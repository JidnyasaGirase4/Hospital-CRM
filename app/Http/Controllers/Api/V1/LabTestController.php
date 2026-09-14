<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabTestResource;
use App\Models\LabOrder;
use App\Models\LabTest;

class LabTestController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', LabOrder::class);

        return $this->success(LabTestResource::collection(LabTest::where('is_active', true)->orderBy('name')->get()));
    }

    public function store()
    {
        $this->authorize('create', LabOrder::class);

        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:lab_tests,code'],
            'sample_type' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reference_range' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $test = LabTest::create($data);

        return $this->success(new LabTestResource($test), 'Lab test created successfully', 201);
    }
}
