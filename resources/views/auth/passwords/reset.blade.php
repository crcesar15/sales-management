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
    <div class="grid">
        <div class="col-12 md:col-8 md:col-offset-2">
            <div class="surface-card p-4 shadow-2 border-round w-full">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}">
                </div>

                <form method="POST" @submit.prevent="resetPassword">
                    <input type="text" value="{{ $token }}" id="token" style="display: none;">
                    <div class="grid">
                        <label for="email"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Email Address') }}</label>
                        <div class="md:col-6 col-12">
                            <input-text type="email" class="w-full" v-model="email" required autocomplete="email"
                                autofocus>
                        </div>
                    </div>

                    <div class="grid">
                        <label for="password"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Password') }}</label>
                        <div class="md:col-6 col-12">
                            <p-password class="w-full" input-class="w-full" v-model="password" :feedback="false"
                                required autocomplete="new-password" toggle-mask>
                        </div>
                    </div>

                    <div class="grid">
                        <label for="password-confirm"
                            class="col-12 md:col-4 flex flex-wrap align-content-center justify-content-start md:justify-content-end">{{ __('Confirm Password') }}</label>
                        <div class="md:col-6 col-12">
                            <p-password class="w-full" input-class="w-full" v-model="password_confirmation"
                                :feedback="false" required autocomplete="new-password" toggle-mask>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-center">
                        <p-button type="submit" :loading="btnLoading">
                            {{ __('Reset Password') }}
                        </p-button>
                        <Toast />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
