@extends('layouts.login')

@section('css')
    <style>
        .register-container {
            margin-top: 20%;
            border-radius: 15px !important;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            height: 100px;
        }
    </style>
@endsection

@section('content')
    <div class="grid" style="height: calc(100vh - 40px)">
        <div class="col-12 md:col-4 md:col-offset-4 flex flex-column justify-content-center">
            <div class="surface-card p-4 shadow-2 border-round w-full">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}">
                </div>

                <form method="POST" @submit.prevent="register">
                    <div class="grid">
                        <label for="name"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Name') }}</label>

                        <div class="col-12 md:col-6">
                            <input-text id="name" class="w-full" type="text" v-model='name' name="name" required
                                autocomplete="name">
                        </div>
                    </div>

                    <div class="grid">
                        <label for="email"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Email Address') }}</label>
                        <div class="col-12 md:col-6">
                            <input-text id="email" class="w-full" type="email" name="email" v-model="email" required
                                autocomplete="email">
                        </div>
                    </div>

                    <div class="grid">
                        <label for="password"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Password') }}</label>
                        <div class="col-12 md:col-6">
                            <p-password id="password" input-class="w-full" class="w-full" v-model="password" toggle-mask
                                :feedback="false" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="grid">
                        <label for="password-confirm"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Confirm Password') }}</label>
                        <div class="col-12 md:col-6">
                            <p-password id="password-confirm" input-class="w-full" class="w-full"
                                v-model="password_confirmation" toggle-mask :feedback="false" required
                                autocomplete="new-password">
                        </div>
                    </div>

                    <div class="grid mt-2 flex justify-content-center">
                        <p-button type="submit">
                            {{ __('Register') }}
                        </p-button>
                        <Toast />
                    </div>
                </form>
                <div class="mt-4" style="text-align: right">
                    <a class="text-primary" href="{{ route('login') }}">
                        {{ __('Already have an account?') }}
                    </a>
                </div>
            </div>
        </div>
    @endsection
