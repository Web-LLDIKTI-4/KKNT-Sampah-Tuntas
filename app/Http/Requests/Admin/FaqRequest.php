<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Faq;
use Illuminate\Validation\Rule;

class FaqRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isUpdate() ? 'update' : 'create', Faq::class) ?? false;
    }

    // answer_type opsional: insert default text, update default tipe record saat ini
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->mergeIfMissing(['answer_type' => $this->currentFaq()?->answer_type ?? Faq::TYPE_TEXT]);
    }

    public function rules(): array
    {
        return [
            'id' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'integer', 'exists:faqs,id'],
            'pertanyaan' => ['required', 'string', 'max:255'],
            'answer_type' => ['string', Rule::in(Faq::TYPES)],
            'jawaban' => ['nullable', 'required_if:answer_type,'.Faq::TYPE_TEXT.','.Faq::TYPE_BOTH, 'string', 'max:10000'],
            'answer_image' => [Rule::requiredIf(fn () => $this->imageRequired()), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    // Wajib untuk tipe image/both, kecuali saat update FAQ yang sudah punya gambar
    private function imageRequired(): bool
    {
        if (! in_array($this->input('answer_type'), [Faq::TYPE_IMAGE, Faq::TYPE_BOTH], true)) {
            return false;
        }

        return blank($this->currentFaq()?->answer_image);
    }

    // Record yang sedang di-update (null saat insert / id tidak valid)
    private function currentFaq(): ?Faq
    {
        $faqId = $this->input('id');
        if (! $this->isUpdate() || ! is_scalar($faqId) || ! ctype_digit((string) $faqId)) {
            return null;
        }

        return once(fn () => Faq::query()->select(['id', 'answer_type', 'answer_image'])->find((int) $faqId));
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Data FAQ tidak valid.',
            'id.integer' => 'Data FAQ tidak valid.',
            'id.exists' => 'Data FAQ tidak ditemukan.',
            'pertanyaan.required' => 'Pertanyaan harus diisi.',
            'pertanyaan.string' => 'Pertanyaan tidak valid.',
            'pertanyaan.max' => 'Pertanyaan maksimal 255 karakter.',
            'answer_type.string' => 'Tipe jawaban tidak valid.',
            'answer_type.in' => 'Tipe jawaban tidak valid.',
            'jawaban.required_if' => 'Jawaban harus diisi.',
            'jawaban.string' => 'Jawaban tidak valid.',
            'jawaban.max' => 'Jawaban maksimal 10000 karakter.',
            'answer_image.required' => 'Gambar jawaban harus diunggah.',
            'answer_image.image' => 'File harus berupa gambar.',
            'answer_image.mimes' => 'Gambar harus berformat jpg, jpeg, png, atau webp.',
            'answer_image.max' => 'Ukuran gambar maksimal 2 MB.',
            'answer_image.uploaded' => 'Gambar gagal diunggah.',
        ];
    }
}
