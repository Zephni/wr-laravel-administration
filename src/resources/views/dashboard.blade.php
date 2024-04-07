@extends('wr-laravel-administration::layouts.auth-layout')

@section('title', 'Dashboard')

@section('content')
    Logged in as {{ $user->name }}
@endsection