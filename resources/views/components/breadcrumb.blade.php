@props(['items' => []])

@if ($items !== [])
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Inicio</a>
            </li>
            @foreach ($items as $item)
                @if ($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $item['label'] }}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        @if (isset($item['url']))
                            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        @else
                            {{ $item['label'] }}
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
