@extends($WRLAHelper::getViewPath("layouts.auth-layout"))

@section('title', 'Login')

@section('content')

    {{-- Login form --}}
    <div class="w-full md:w-9/12 lg:w-4/12 rounded-lg p-8 mx-6 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="text-center">
            <h1 class="text-2xl font-medium">Welcome Back!</h1>
            <hr class="w-full md:w-1/3 h-2 my-4 m-auto border-t-2 border-slate-500 dark:border-slate-200" />
        </div>

        @if(session('success'))
            @themeComponent('alert', ['type' => 'success', 'message' => session('success')])
        @elseif(session('error'))
            @themeComponent('alert', ['type' => 'error', 'message' => session('error')])
        @endif

        <form action="{{ route('wrla.login.post') }}" method="post" class="flex flex-col gap-6">
            @csrf

            <div>
                @themeComponent('forms.input-text', [
                    'label' => 'Email Address',
                    'error' => $errors->first('email'),
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'type' => 'email',
                        'name' => 'email',
                        'value' => old('email'),
                        'autofocus' => true,
                        'required' => true,
                    ])
                ])
            </div>

            <div>
                @themeComponent('forms.input-text', [
                    'label' => 'Password',
                    'error' => $errors->first('password'),
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'type' => 'password',
                        'name' => 'password',
                        'value' => '',
                        'required' => true,
                    ])
                ])
            </div>

            <div class="flex justify-between">
                @themeComponent('forms.input-checkbox', [
                    'label' => 'Remember me',
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'name' => 'remember',
                        'checked' => old('remember'),
                    ])
                ])

                <a href="{{ route('wrla.forgot-password') }}" class="whitespace-nowrap text-sm text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-200">
                    Forgot your password?
                </a>
            </div>

            <div>
                @themeComponent('forms.button', [
                    'size' => 'large',
                    'text' => 'Login',
                    'icon' => 'fa fa-sign-in-alt',
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'type' => 'submit'
                    ])
                ])
            </div>

        </form>

    </div>

@endsection
