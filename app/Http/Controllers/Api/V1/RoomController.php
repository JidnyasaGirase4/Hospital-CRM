<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Beds\StoreRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::query()
            ->with('beds')
            ->when($request->filled('ward_id'), fn ($q) => $q->where('ward_id', $request->integer('ward_id')))
            ->get();

        return $this->success(RoomResource::collection($rooms));
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return $this->success(new RoomResource($room), 'Room created successfully', 201);
    }
}
