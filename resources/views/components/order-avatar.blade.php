{{--
    Order profile picture avatar with text-initials fallback.

    Props:
        $url        — nullable Facebook photo URL
        $initials   — pre-computed initials string (e.g. "AO23")
        $size       — pixel size of the circle (default 32)
        $fontSize   — font-size for initials text (default ".72rem")
        $class      — extra CSS classes
--}}
@props([
    'url'      => null,
    'initials' => '?',
    'size'     => 32,
    'fontSize' => '.72rem',
    'class'    => '',
])

@php
    $uid = 'av-' . uniqid();
@endphp

<span class="order-avatar-wrap flex-shrink-0 {{ $class }}"
      style="display:inline-block;position:relative;width:{{ $size }}px;height:{{ $size }}px">

    {{-- Text fallback (shown when no URL or image fails to load) --}}
    <span id="{{ $uid }}-fallback"
          aria-hidden="true"
          style="
              position:absolute;inset:0;
              border-radius:50%;
              background:linear-gradient(135deg,#6c757d,#495057);
              color:#fff;
              display:flex;
              align-items:center;
              justify-content:center;
              font-size:{{ $fontSize }};
              font-weight:700;
              letter-spacing:-.5px;
              user-select:none;
              {{ $url ? 'z-index:0' : 'z-index:1' }}
          ">
        {{ $initials }}
    </span>

    @if($url)
        {{-- Photo on top; on error it hides itself and the fallback shows --}}
        <img src="{{ $url }}"
             alt=""
             loading="lazy"
             style="
                 position:absolute;inset:0;
                 width:{{ $size }}px;height:{{ $size }}px;
                 border-radius:50%;
                 object-fit:cover;
                 z-index:1;
             "
             onerror="this.style.display='none';document.getElementById('{{ $uid }}-fallback').style.zIndex=1;">
    @endif

</span>
