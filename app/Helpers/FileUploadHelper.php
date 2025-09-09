<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
    public static function upload(UploadedFile $file, $folder)
    {
        return $file->store($folder, 'public');
    }
}
