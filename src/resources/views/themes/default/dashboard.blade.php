@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Dashboard')

@section('content')
    {{-- Title --}}
    <x-wrla::heading title="Dashboard" icon="fa-solid fa-tachometer-alt" />

    {{-- Notifications widget --}}
    <div>
        @livewire('wrla.notifications-widget', [
            'userIds' => config('wr-laravel-administration.dashboard.notifications.user_groups'),
        ])
    </div>

    {{-- Customise dashboard by copying this file to /resources/views/vendor/wrla/themes/default/dashboard.blade.php --}}
@endsection
