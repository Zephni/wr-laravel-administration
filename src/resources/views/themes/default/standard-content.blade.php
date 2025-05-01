@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', $title ?? 'Title not set')

@section('content')
    {!! $content !!}
@endsection
