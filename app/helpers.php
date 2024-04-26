<?php

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

function uploadImage($file, $file_type, $file_name, $width, $height)
{
    $img = Image::make($file);
    $img->resize($width, $height);
    Storage::disk('s3')->put($file_type . '/' . $file_name, $img->encode());
    return null;

    //pipeline test
}
