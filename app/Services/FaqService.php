<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Simpan/hapus FAQ beserta file gambar jawaban (disk public, folder faq)
class FaqService
{
    public function save(Faq $faq, array $data, ?UploadedFile $image): Faq
    {
        $oldImage = $faq->answer_image;
        $newImage = null;

        if ($data['answer_type'] === Faq::TYPE_IMAGE) {
            $data['jawaban'] = null;
        }

        if ($data['answer_type'] === Faq::TYPE_TEXT) {
            $data['answer_image'] = null;
        } elseif ($image) {
            // Nama acak dari server, bukan nama asli dari klien
            $newImage = $image->store(Faq::IMAGE_DIR, Faq::DISK);
            $data['answer_image'] = $newImage;
        }

        try {
            $faq->fill($data)->save();
        } catch (Throwable $e) {
            if ($newImage) {
                Storage::disk(Faq::DISK)->delete($newImage);
            }
            throw $e;
        }

        if ($oldImage && $oldImage !== $faq->answer_image) {
            Storage::disk(Faq::DISK)->delete($oldImage);
        }

        return $faq;
    }

    public function delete(Faq $faq): void
    {
        $image = $faq->answer_image;
        $faq->delete();

        if ($image) {
            Storage::disk(Faq::DISK)->delete($image);
        }
    }
}
