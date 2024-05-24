<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FilesTrait
{

    public function uploadFile(UploadedFile $file, $directory = 'uploads', $customName )
    {
        // Generate a unique filename
        $filename = $customName . '.' . $file->getClientOriginalExtension();
        // Store the file
        $path = $file->storeAs($directory, $filename, 'public');
        return $path;
    }

    public function deleteFile($filePath)
    {
        // Check if the file exists
        if (Storage::disk('public')->exists($filePath)) {
            // Delete the file
            Storage::disk('public')->delete($filePath);
            return true;
        }
        return false;
    }
}
