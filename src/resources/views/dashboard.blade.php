@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Dashboard')

@section('content')
    <p>Using theme: {{ data_get($themeData, 'name') }}</p>
@endsection
