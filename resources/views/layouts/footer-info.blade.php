{{-- Shared footer columns (dashboard + landing): LLDIKTI sites + contact --}}
@php($cols = $cols ?? ['col-lg-3 col-md-6 col-sm-6', 'col'])
<div class="{{ $cols[0] }}">
  <h6 class="footer-title mb-4 mb-lg-6">Laman LLDIKTI Wilayah IV</h6>
  <ul class="list-unstyled mb-0">
    <li class="mb-4">
      <a href="https://gradasi.lldikti4.id/" target="_blank" rel="noopener noreferrer" class="footer-link d-inline-flex align-items-center gap-2"><i class="ri-external-link-line" aria-hidden="true"></i>GRADASI</a>
    </li>
    <li class="mb-4">
      <a href="https://empat.lldikti4.id/" target="_blank" rel="noopener noreferrer" class="footer-link d-inline-flex align-items-center gap-2"><i class="ri-external-link-line" aria-hidden="true"></i>EMPAT</a>
    </li>
  </ul>
</div>
<div class="{{ $cols[1] }}">
  <h6 class="footer-title mb-4 mb-lg-6">Kontak Kami</h6>
  <ul class="list-unstyled mb-0">
    <li class="mb-4 d-flex gap-2">
      <i class="ri-map-pin-line footer-text" aria-hidden="true"></i>
      <span class="footer-link">Jl. K.H.P. Hasan Mustofa No.38 Bandung 40124</span>
    </li>
    <li class="mb-4 d-flex gap-2">
      <i class="ri-mail-line footer-text" aria-hidden="true"></i>
      <a href="mailto:informasi@lldikti4.id" class="footer-link text-break">informasi@lldikti4.id</a>
    </li>
    <li class="mb-4 d-flex gap-2">
      <i class="ri-phone-line footer-text" aria-hidden="true"></i>
      <span class="footer-text"><a href="tel:+62227275630" class="footer-link">022-727-5630</a>, <a href="tel:+6282244121226" class="footer-link">0822-4412-1226</a></span>
    </li>
  </ul>
</div>
