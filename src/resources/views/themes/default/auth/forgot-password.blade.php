@extends($WRLAHelper::getViewPath("layouts.auth-layout"))

@section('title', 'Forgot Password')

@section('content')

    {{-- Login form --}}
    <div class="w-full md:w-9/12 lg:w-4/12 rounded-lg p-8 mx-6 bg-slate-100 shadow-md dark:bg-slate-800">
        {{-- Back link using text-primary-500 with icon --}}
        <a href="{{ route('wrla.login') }}" class="relative flex items-center gap-2 text-primary-500 -top-5 -left-3">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>

        {{-- Title --}}
        <div class="text-center">
            <h1 class="text-2xl font-medium">Forgotten Your Password?</h1>
            <hr class="w-full md:w-1/3 h-2 my-4 m-auto border-t-2 border-slate-500 dark:border-slate-200" />
        </div>

        @if(session('success'))
            @themeComponent('alert', ['type' => 'success', 'message' => session('success')])
        @elseif(session('error'))
            @themeComponent('alert', ['type' => 'error', 'message' => session('error')])
        @endif

        <form action="{{ route('wrla.forgot-password.post') }}" method="post" class="flex flex-col gap-6">
            @csrf

            <div>
                @themeComponent('forms.input-text', [
                    'label' => 'Email Address',
                    'type' => 'email',
                    'name' => 'email',
                    'value' => old('email'),
                    'error' => $errors->first('email'),
                    'placeholder' => 'email@address.com',
                    'required' => true,
                    'autofocus' => true
                ])
            </div>

            <div>
                @themeComponent('forms.input-button', [
                    'type' => 'submit',
                    'text' => 'Request password reset link',
                    'class' => 'w-full'
                ])
            </div>

        </form>

    </div>

@endsection