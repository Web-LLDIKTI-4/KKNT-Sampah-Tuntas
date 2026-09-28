<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\Profile\PhotoUploadRequest;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;

trait ManagesProfilePhoto
{
    public function uploadpoto()
    {
        return view('profile.uploadpoto');
    }

    public function prosesuploadpoto(PhotoUploadRequest $request, ProfilePhotoService $photos)
    {
        $photos->store($request->user(), $request->file('file_upload'));

        return response()->json(['success' => true, 'message' => 'Poto berhasil diubah.. silahkan refresh halaman!']);
    }

    public function getPoto(Request $request, ProfilePhotoService $photos)
    {
        return $photos->response($request->user());
    }
}
