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
        <div class="md:col-6 col-12 md:col-offset-3">
            <div class="surface-100 p-4 shadow-2 border-round">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}">
                </div>
                <form method="POST" @submit.prevent="sentResetLink">
                    <div class="grid">
                        <div class="md:col-4 flex flex-wrap align-content-center justify-content-end">
                            <label for="email">{{ __('Email Address') }}</label>
                        </div>
                        <div class="md:col-6">
                            <input-text class="w-full" id="email" required autocomplete="email" v-model="email">
                        </div>
                    </div>

                    <div class="grid mt-2">
                        <div class="md:col-8 md:col-offset-2 flex flex-wrap justify-content-center">
                            <p-button type="submit" :loading="btnLoading">
                                {{ __('Send Password Reset Link') }}
                            </p-button>
                            <Toast />
                        </div>
                    </div>
                </form>
                <div class="mt-3" style="text-align: right">
                    <a class="text-primary" href="{{ route('login') }}">
                        {{ __('Go Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
