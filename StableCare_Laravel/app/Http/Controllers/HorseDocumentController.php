<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Models\HorseDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HorseDocumentController extends Controller
{
    public function index(Horse $horse): View
    {
        $this->authorizeHorse($horse);
        return view('documents.index', ['horse' => $horse, 'documents' => $horse->documents()->with('uploader')->latest()->paginate(15)]);
    }

    public function store(Request $request, Horse $horse): RedirectResponse
    {
        $this->authorizeHorse($horse);
        abort_unless($request->user()->canManageHealth(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'document_type' => ['required', 'in:Pase,Veterinārā izziņa,Vakcinācijas apliecība,Analīzes,Cits'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $request->file('document');
        $safeName = Str::uuid()->toString().'.'.$file->guessExtension();
        $path = $file->storeAs('horse-documents/'.$horse->id, $safeName, 'local');
        $horse->documents()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
        ]);
        return back()->with('success', 'Dokuments augšupielādēts drošā glabātuvē.');
    }

    public function download(Horse $horse, HorseDocument $document): StreamedResponse
    {
        $this->authorizeHorse($horse);
        abort_unless($document->horse_id === $horse->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Fails nav atrasts.');
        return Storage::disk('local')->download($document->file_path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, Horse $horse, HorseDocument $document): RedirectResponse
    {
        $this->authorizeHorse($horse);
        abort_unless($request->user()->canManageHealth(), 403);
        abort_unless($document->horse_id === $horse->id, 404);
        $document->delete();
        return back()->with('success', 'Dokuments dzēsts.');
    }

    private function authorizeHorse(Horse $horse): void
    {
        abort_unless($horse->stable_id !== null && $horse->stable_id === auth()->user()->stable_id, 404);
    }
}
