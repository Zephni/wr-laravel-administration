@extends($WRLAHelper::getViewPath("layouts.admin-layout"))

@section('title', 'Dashboard')

@section('content')
    {{-- Styled dashboard title --}}
    <div class="pb-6">
        <h1 class="text-2xl font-light">
            <i class="fas fa-tachometer-alt text-slate-500 mr-1"></i>
            Dashboard
        </h1>
        <hr class="border-b border-slate-300 w-80 mt-1 mb-3">
        {{-- <h2 class="text-xl font-thin">The dashboard is currently under development</h2> --}}
    </div>
    {{-- <p>Using theme: <b>{{ data_get($WRLAThemeData, 'name') }}</b></p> --}}

    {{-- Notifications widget --}}
    <div>
        <h3 class="text-lg font-light text-slate-600 mb-3">
            <i class="fas fa-bell text-slate-500 mr-1"></i>
            Notifications / Tasks
        </h3>
        @livewire('wrla.notifications-widget')
    </div>
@endsection
