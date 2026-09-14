<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RadiologyTestResource;
use App\Models\RadiologyOrder;
use App\Models\RadiologyTest;

class RadiologyTestController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', RadiologyOrder::class);

        return $this->success(RadiologyTestResource::collection(RadiologyTest::where('is_active', true)->orderBy('name')->get()));
    }

    public function store()
    {
        $this->authorize('create', RadiologyOrder::class);

        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:radiology_tests,code'],
            'modality' => ['required', 'in:X-Ray,CT,MRI,Ultrasound,Other'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $test = RadiologyTest::create($data);

        return $this->success(new RadiologyTestResource($test), 'Radiology test created successfully', 201);
    }
}
