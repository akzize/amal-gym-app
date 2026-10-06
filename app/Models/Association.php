<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Association extends Model
{
    protected $guarded = [];

    /**
     * The logo as a base64 data URI (for receipts), or null when no logo file exists.
     * Embedding it works whatever disk/visibility the upload was stored with.
     */
    public function logoDataUri(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        $disk = Storage::disk(config('filament.default_filesystem_disk', config('filesystems.default')));

        if ($disk->exists($this->logo)) {
            $contents = $disk->get($this->logo);
            $mime = $disk->mimeType($this->logo);
        } elseif (is_file(public_path($this->logo))) {
            $contents = file_get_contents(public_path($this->logo));
            $mime = mime_content_type(public_path($this->logo));
        } else {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}
