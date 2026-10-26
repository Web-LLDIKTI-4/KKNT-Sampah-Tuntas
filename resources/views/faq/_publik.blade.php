@php
    $faqList ??= collect();
@endphp
<div class="faq-accordion accordion" id="faqAccordion">
    @foreach ($faqList as $faq)
        <div class="accordion-item faq-item">
            <h3 class="accordion-header" id="faq-{{ $faq->id }}-head">
                <button class="accordion-button collapsed faq-question" type="button" data-bs-toggle="collapse"
                    data-bs-target="#faq-{{ $faq->id }}" aria-expanded="false" aria-controls="faq-{{ $faq->id }}">
                    <span class="faq-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="faq-question-text">{{ $faq->pertanyaan }}</span>
                </button>
            </h3>
            <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse" aria-labelledby="faq-{{ $faq->id }}-head">
                <div class="accordion-body faq-answer">
                    @if ($faq->hasText())
                        <div class="faq-answer-text">{{ $faq->jawaban }}</div>
                    @endif
                    @if ($faq->hasImage())
                        <img src="{{ $faq->answer_image_url }}" alt="{{ $faq->pertanyaan }}"
                            class="faq-answer-img" loading="lazy" decoding="async">
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
