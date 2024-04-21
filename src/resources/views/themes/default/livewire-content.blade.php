@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Browse')

@section('content')
    {{-- @dd($livewireComponentAlias, $livewireComponentData) --}}
    @livewire($livewireComponentAlias, $livewireComponentData)
@endsection
