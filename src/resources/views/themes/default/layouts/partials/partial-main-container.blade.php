{{-- Right container --}}
<div id="wrla_main_container"
    x-bind:style="'width: ' + (window.innerWidth < 768 ? window.innerWidth : window.innerWidth - leftPanelAttemptedWidth) + 'px;'"
    class="absolute md:relative flex-1 h-full">
    {{-- Top bar --}}
    <div style="z-index: 5;" class="relative left-0 flex w-full gap-5 h-9 justify-between items-center border-b-2 border-slate-300 dark:border-slate-700 shadow-md dark:shadow-slate-900 bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
        {{-- Version --}}
        <div>
            @if($WRLAHelper::userIsDev())
                <div class="hidden md:inline-block pl-6 text-sm">
                    @php
                        $versionHandlerClass = \WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionHandler::class;
                        $localComposerVersion = $versionHandlerClass::$localPackageCurrentVersion;
                        $localCurrentSha = $versionHandlerClass::$localPackageCurrentSha;
                        $remotePackageLatestSha = $versionHandlerClass::$remotePackageLatestSha;
                    @endphp
                    Version:
                    {{ $localComposerVersion }}
                    <span class="px-1.5">-</span>
                    @if($localCurrentSha === $remotePackageLatestSha)
                        <b onclick="window.loadLivewireModal(this, 'dev-tools.dev-tools-modal')"
                            class="cursor-help"
                            title="Current sha: {{ $localCurrentSha }}">
                            <i class="fas fa-info-circle text-xs text-slate-400"></i>
                            <span class="pl-1">Up to date</span>
                        </b>
                    @else
                        <button
                            onclick="window.loadLivewireModal(this, 'dev-tools.dev-tools-modal')"
                            class="text-sky-600 font-semibold cursor-pointer"
                            title="Current sha: {{ $localCurrentSha }} - Latest sha: {{ $remotePackageLatestSha }}">
                            <i class="fas fa-exclamation-triangle text-sky-600"></i>
                            <span class="pl-1">Update available</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-row h-full items-center">
            <div class="relative flex items-center gap-x-4 h-full text-slate-500 dark:text-slate-400 text-sm mr-4">
                <span class="text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    <span>Logged in as</span>
                    <i class="fas fa-user mx-1"></i>
                    {{ $user->wrlaUserData?->getFullName() }}
                </span>
            </div>
            <button @click="darkMode = !darkMode" class="flex w-[40px] h-full justify-center items-center border-l border-slate-300 dark:border-slate-700 bg-slate-50 text-slate-800 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 dark:text-slate-400">
                <i class="fas fa-sun text-sm text-primary-500 dark:hidden"></i>
                <i class="fas fa-moon text-sm text-primary-500 hidden dark:block"></i>
            </button>
            <a href="{{ route('wrla.logout') }}" class="flex h-full justify-center items-center text-sm gap-2 px-3 border-l border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 text-primary-500">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    {{-- Top bar gap, removed as we have temporarily stopped the top bar being fixed --}}
    {{-- <div class="block w-full h-9"></div> --}}

    {{-- Content container --}}
    <div class="flex flex-row w-full h-full">
        {{-- Yield content --}}
        <div class="relative w-full h-full flex flex-col pt-8 pl-3 pr-2 lg:pl-14 lg:pr-10">
            @if(session('success'))
                @themeComponent('alert', ['type' => 'success', 'message' => session('success')])
            @elseif(session('error'))
                @themeComponent('alert', ['type' => 'error', 'message' => session('error')])
            @endif

            @yield('content')
        </div>
    </div>
</div>
