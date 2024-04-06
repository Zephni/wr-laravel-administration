@extends('wr-laravel-administration::layouts.auth-layout')

@section('title', 'Login')

@section('content')

    {{-- Login form --}}
    <div class="w-full md:w-9/12 lg:w-4/12 rounded-lg p-8 mx-6 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="text-center">
            <h1 class="text-2xl font-medium">Welcome Back!</h1>
            <hr class="w-full md:w-1/3 h-2 my-4 m-auto border-t-2 border-slate-500 dark:border-slate-200" />
        </div>

        @if(session('error'))
            <x-wr-laravel-administration::alert type="error" :message="session('error')" />
        @endif
        
        <form action="{{ route('wrla.login.post') }}" method="post" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-wr-laravel-administration::forms.input-text
                    label="Email Address"
                    type="email"
                    name="email"
                    :value="old('email')"
                    :error="$errors->first('email')"
                    required autofocus />
            </div>

            <div>
                <x-wr-laravel-administration::forms.input-text
                    label="Password"
                    type="password"
                    name="password"
                    value=""
                    :error="$errors->first('password')"
                    required />
            </div>

            <div class="flex justify-between">
                <x-wr-laravel-administration::forms.input-checkbox
                    label="Remember me"
                    name="remember" />

                <a href="#" class="text-sm text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-200">Forgot your password?</a>
            </div>

            <div>
                <x-wr-laravel-administration::forms.input-button type="submit" text="Login" class="w-full" />
            </div>

        </form>

    </div>

@endsection