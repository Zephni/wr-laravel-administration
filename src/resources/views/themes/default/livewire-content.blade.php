@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Browse')

@section('content')
    @livewire($livewireComponentAlias, $livewireComponentData)
@endsection
