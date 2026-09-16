@php
    $classPrefix ='main-banner';
    $dataPrefix ='data-main-banner';

    $banner = \App\Models\Banner::where('is_show', true)->first();

    $kyivDesktop = $banner ? $banner->getFirstMediaUrl('kyiv_desktop') : null;
    $kyivMobile  = $banner ? $banner->getFirstMediaUrl('kyiv_mobile') : null;
@endphp

<div {{$dataPrefix}} class="{{$classPrefix}}"
     data-kyiv-desktop="{{ $kyivDesktop }}"
     data-kyiv-mobile="{{ $kyivMobile }}">

    <div {{$dataPrefix}}-background class="{{$classPrefix}}__background">
        <div {{$dataPrefix}}-background-loader class="{{$classPrefix}}__background-loader">
            @include('components.loader.loader')
        </div>

        <video {{$dataPrefix}}-background-video id="banner-video" class="{{$classPrefix}}__background-video"
               autoplay loop muted playsinline></video>
    </div>

    <div class="{{$classPrefix}}__content">
        <h1 class="{{$classPrefix}}__title">{{ __('static.banner_title') }}</h1>
        <p class="{{$classPrefix}}__subtitle">{{ __('static.banner_subtitle') }}</p>
    </div>

</div>
