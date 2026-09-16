@php
    $classPrefix = 'region-selector';
    $dataPrefix = 'data-region-selector';
@endphp

<div {{$dataPrefix}} class="{{$classPrefix}}">
    <div class="{{$classPrefix}}__content">
        <div class="{{$classPrefix}}__flags">

            <a href="https://thefame.ua" class="{{$classPrefix}}__flag" style="text-decoration: none; color: inherit;">
                <img src="{{ asset('img/flags/ua-flag.webp') }}" alt="Ukraine">
                <div class="{{$classPrefix}}__info">
                    <span class="{{$classPrefix}}__name">UKRAINE</span>
                    <span class="{{$classPrefix}}__city">THE FAME KYIV</span>
                </div>
            </a>

            <a href="https://thefame.ae" class="{{$classPrefix}}__flag" style="text-decoration: none; color: inherit;">
                <img src="{{ asset('img/flags/dubai-flag.webp') }}" alt="OAE">
                <div class="{{$classPrefix}}__info">
                    <span class="{{$classPrefix}}__name">OAE</span>
                    <span class="{{$classPrefix}}__city">THE FAME DUBAI</span>
                </div>
            </a>

            <div class="{{$classPrefix}}__flag {{$classPrefix}}__flag--disabled">
                <img src="{{ asset('img/flags/pl-flag.webp') }}" alt="Poland">
                <div class="{{$classPrefix}}__info">
                    <span class="{{$classPrefix}}__name">POLAND</span>
                    <span class="{{$classPrefix}}__city">THE FAME WARSAW <br><small>(coming soon)</small></span>
                </div>
            </div>

        </div>
    </div>
</div>
