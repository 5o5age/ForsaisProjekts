<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HorseDocument extends Model
{
    protected $fillable = ['horse_id', 'uploaded_by', 'title', 'document_type', 'file_path', 'original_name', 'mime_type', 'file_size'];
    protected function casts(): array { return ['file_size' => 'integer']; }
    public function horse() { return $this->belongsTo(Horse::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    protected static function booted(): void
    {
        static::deleting(function (HorseDocument $document): void {
            Storage::disk('local')->delete($document->file_path);
        });
    }
}
