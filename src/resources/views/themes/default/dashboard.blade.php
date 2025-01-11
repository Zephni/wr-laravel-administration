@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Dashboard')

@section('content')
    {{-- Styled dashboard title --}}
    <div class="pb-6">
        <h1 class="text-2xl font-light">
            <i class="fas fa-tachometer-alt text-slate-700 dark:text-white mr-1"></i>
            Dashboard
        </h1>
        <hr class="border-b border-slate-400 w-80 mt-1 mb-3">
    </div>

    {{-- Notifications widget --}}
    <div>
        @livewire('wrla.notifications-widget', [
            'userIds' => $WRLAHelper::interpretUserGroupsArray(
                config('wr-laravel-administration.dashboard.notifications.user_groups')
            ),
        ])
    </div>

    {{-- Customise dashboard by copying this file to /resources/views/vendor/wrla/themes/default/dashboard.blade.php --}}
@endsection
