<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    protected function storeImage(?UploadedFile $file, string $folder, ?string $old = null): ?string
    {
        if (! $file) {
            return $old;
        }
        if ($old) {
            \Storage::disk('public')->delete($old);
        }

        return $file->store($folder, 'public');
    }

    protected function authorizeOwner(Pet $pet): void
    {
        abort_unless(auth()->id() === $pet->user_id || auth()->user()->isAdmin(), 403);
    }
}
