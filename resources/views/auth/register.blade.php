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
    <div class="grid grid-cols-12" style="height: calc(100vh - 40px)">
        <div
            class="col-span-12 md:col-span-6 md:col-start-4 lg:col-span-4 lg:col-start-5 mx-4 md:mx-0 flex flex-col justify-center">
            <div class="bg-surface-0 p-10 shadow-lg rounded-border w-full">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}">
                </div>

                <form method="POST" @submit.prevent="register">
                    <div class="grid grid-cols-12 gap-2">
                        <label for="name"
                            class="col-span-12 md:col-span-4 flex flex-wrap content-center justify-start md:justify-end">{{ __('Name') }}</label>

                        <div class="col-span-12 md:col-span-6">
                            <input-text id="name" class="w-full" type="text" v-model='name' name="name" required
                                autocomplete="name">
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <label for="email"
                            class="col-span-12 md:col-span-4 flex flex-wrap content-center justify-start md:justify-end">{{ __('Email Address') }}</label>
                        <div class="col-span-12 md:col-span-6">
                            <input-text id="email" class="w-full" type="email" name="email" v-model="email" required
                                autocomplete="email">
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <label for="password"
                            class="col-span-12 md:col-span-4 flex flex-wrap content-center justify-start md:justify-end">{{ __('Password') }}</label>
                        <div class="col-span-12 md:col-span-6">
                            <p-password id="password" input-class="w-full" class="w-full" v-model="password" toggle-mask
                                :feedback="false" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <label for="password-confirm"
                            class="col-span-12 md:col-span-4 flex flex-wrap content-center justify-start md:justify-end">{{ __('Confirm Password') }}</label>
                        <div class="col-span-12 md:col-span-6">
                            <p-password id="password-confirm" input-class="w-full" class="w-full"
                                v-model="password_confirmation" toggle-mask :feedback="false" required
                                autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mt-2 flex justify-center">
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
