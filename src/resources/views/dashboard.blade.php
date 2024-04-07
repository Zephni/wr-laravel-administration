@extends('wr-laravel-administration::layouts.auth-layout')

@section('title', 'Dashboard')

@section('content')
    Logged in as {{ \App\WRLA\User::current()->name }}
@endsection