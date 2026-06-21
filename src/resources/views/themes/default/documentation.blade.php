@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Documentation')

@section('content')
    <iframe
        wire:ignore
        src="{{ route('wrla.documentation.static', ['path' => 'index.html']) }}?t={{ time() }}"
        class="relative border-0 bg-white"
        frameborder="0"
        title="WRLA Documentation"
        style="
            left: -50px;
            top: -30px;
            width: calc(100% + 85px);
            height: calc(100% - 0px);
        ">
    </iframe>
@endsection
