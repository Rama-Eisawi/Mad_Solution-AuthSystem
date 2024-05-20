<?php
   namespace App\Traits;
   use Illuminate\Support\Str;
   use Illuminate\Http\UploadedFile;

   trait FilesTrait {

     public function uploadFile(UploadedFile $file, $directory = 'uploads') {

       // Generate a unique filename
       $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
       // Store the file
       $path = $file->storeAs($directory, $filename, 'public');
       return $path;

     }
}
