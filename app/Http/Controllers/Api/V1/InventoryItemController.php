<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\StoreInventoryTransactionRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($items, InventoryItemResource::class, 'Inventory items retrieved successfully');
    }

    public function store(StoreInventoryItemRequest $request)
    {
        $item = $this->inventoryService->createItem($request->validated());

        return $this->success(new InventoryItemResource($item), 'Inventory item created successfully', 201);
    }

    public function show(InventoryItem $inventoryItem)
    {
        $this->authorize('view', $inventoryItem);

        return $this->success(new InventoryItemResource($inventoryItem));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem)
    {
        $item = $this->inventoryService->updateItem($inventoryItem, $request->validated());

        return $this->success(new InventoryItemResource($item), 'Inventory item updated successfully');
    }

    public function storeTransaction(StoreInventoryTransactionRequest $request, InventoryItem $inventoryItem)
    {
        $transaction = $this->inventoryService->recordTransaction($inventoryItem, $request->validated());

        return $this->success(new InventoryTransactionResource($transaction), 'Inventory transaction recorded successfully', 201);
    }

    public function lowStock()
    {
        $this->authorize('viewAny', InventoryItem::class);

        return $this->success(InventoryItemResource::collection($this->inventoryService->lowStock()));
    }
}
