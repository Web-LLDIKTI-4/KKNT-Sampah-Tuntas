<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Foto profil disimpan di disk privat (tidak bisa diakses langsung dari web).
 */
class ProfilePhotoService
{
    private const DISK = 'local';

    private const DIR = 'photo';

    public function store(User $user, UploadedFile $file): void
    {
        $disk = Storage::disk(self::DISK);
        if ($user->image && $disk->exists(self::DIR.'/'.$user->image)) {
            $disk->delete(self::DIR.'/'.$user->image);
        }

        // Nama acak dari server, bukan nama asli dari klien
        $path = $file->store(self::DIR, self::DISK);
        $user->forceFill(['image' => basename($path)])->save();
    }

    public function response(User $user): BinaryFileResponse
    {
        $disk = Storage::disk(self::DISK);
        $path = self::DIR.'/'.basename((string) $user->image);

        if ($user->image && $disk->exists($path)) {
            return response()->file($disk->path($path));
        }

        return response()->file(public_path('assets/img/avatars/1.png'));
    }
}
