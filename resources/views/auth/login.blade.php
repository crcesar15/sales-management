@extends('layouts.login')

@section('css')
    <style>
        .login-container {
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
            class="col-span-12 lg:col-span-4 lg:col-start-5 md:col-span-6 md:col-start-4 mx-4 md:mx-0 flex flex-col justify-center">
            <div class="bg-surface-0 pt-20 pb-8 px-6 shadow-lg rounded-border w-full">
                <form method="POST" @submit.prevent="login">
                    <!--Add logo from public storage-->
                    <div class="logo-container">
                        <img src="{{ asset('images/logo.png') }}" alt="logo">
                    </div>
                    <div class="flex flex-col gap-2 mt-4">
                        <label for="username">Username</label>
                        <input-text class="w-full" v-model="username" autocomplete="username" input-id="username"
                            :required="true" />
                    </div>
                    <div class="flex flex-col gap-2 mt-4">
                        <label for="password">Password</label>
                        <p-password class="w-full" input-class="w-full" toggle-mask v-model="password" input-id="password"
                            :required="true" :feedback="false" />
                    </div>
                    <div class="flex flex-row-reverse justify-end mt-4">
                        <label class="text-danger" for="remember">Remember Me </label>
                        <checkbox binary v-model="remember" input-id="remember" />
                    </div>
                    <div class="flex w-full mt-3 gap-2">
                        <div class="w-full">
                            <p-button type="submit" class="w-full" label="{{ __('Login') }}"></p-button>
                        </div>
                        <div class="w-full">
                            <p-button type="button" severity="primary" class="w-full" outlined label="{{ __('Register') }}"
                                @click="redirect('/register')"></p-button>
                        </div>
                    </div>
                    <div class="flex justify-end w-full mt-2">
                        @if (Route::has('password.request'))
                            <a class="btn btn-link text-color-secondary" href="{{ route('password.request') }}">
                                {{ __('Forgot Your Password?') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        <Toast />
    </div>
@endsection
