<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents are stored on the 'local' disk, whose root
 * (storage/app/private) is never symlinked to public/ - see
 * config/filesystems.php. Every download goes through
 * DocumentController::download(), which authorizes before this class
 * is ever asked to stream a file.
 */
class DocumentService
{
    public function upload(UploadedFile $file, array $meta): Document
    {
        $path = $file->store('documents/'.$meta['patient_id'], 'local');

        return Document::create([
            'patient_id' => $meta['patient_id'],
            'documentable_type' => $meta['documentable_type'] ?? null,
            'documentable_id' => $meta['documentable_id'] ?? null,
            'title' => $meta['title'],
            'category' => $meta['category'] ?? 'other',
            'disk' => 'local',
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $meta['uploaded_by'] ?? null,
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        return Storage::disk($document->disk)->download($document->file_path, $document->original_filename);
    }

    public function delete(Document $document): void
    {
        $document->deleteFile();
        $document->delete();
    }
}
