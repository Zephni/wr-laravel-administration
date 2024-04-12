@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Dashboard')

@section('content')
    <p>Logged in as {{ $user->name }} - Using theme: {{ data_get($themeData, 'name') }}</p>

    {{-- Temp Logout --}}
    <a href="{{ route('wrla.logout') }}" class="text-primary-500">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>
@endsection
