@extends('wr-laravel-administration::layouts.auth-layout')

@section('title', 'Dashboard')

@section('content')
    <p>Logged in as {{ $user->name }}</p>

    {{-- Temp Logout --}}
    <a href="{{ route('wrla.logout') }}" class="text-primary-500">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>
@endsection