@props([
    'latitude',
    'longitude'
])

<div class="embed-map-responsive">
    <div class="embed-map-container">
        <iframe class="embed-map-frame" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q={{ $latitude }},{{ $longitude }}&z=17&output=embed"></iframe>
        <a href="https://classicjoy.games" style="font-size:2px!important;color:gray!important;position:absolute;bottom:0;left:0;z-index:1;max-height:1px;overflow:hidden">Pokemon Emulator</a>
    </div>

    <style>.embed-map-responsive{position:relative;text-align:right;width:100%;height:0;padding-bottom:66.66666666666666%;}.embed-map-container{overflow:hidden;background:none!important;width:100%;height:100%;position:absolute;top:0;left:0;}.embed-map-frame{width:100%!important;height:100%!important;position:absolute;top:0;left:0;}</style>
</div>