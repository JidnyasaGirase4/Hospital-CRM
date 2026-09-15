<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($documents, DocumentResource::class, 'Documents retrieved successfully');
    }

    public function store(StoreDocumentRequest $request)
    {
        $document = $this->documentService->upload($request->file('file'), [
            ...$request->validated(),
            'uploaded_by' => $request->user()->id,
        ]);

        return $this->success(new DocumentResource($document), 'Document uploaded successfully', 201);
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        return $this->success(new DocumentResource($document->load('uploadedBy')));
    }

    public function download(Document $document)
    {
        $this->authorize('download', $document);

        return $this->documentService->download($document);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return $this->success(null, 'Document deleted successfully');
    }
}
