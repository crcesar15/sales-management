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
    <div class="grid" style="height: calc(100vh - 40px)">
        <div class="col-12 md:col-4 md:col-offset-4 flex flex-column justify-content-center">
            <div class="surface-card pt-8 pb-5 px-4 shadow-2 border-round w-full">
                <form method="POST" @submit.prevent="login">
                    <!--Add logo from public storage-->
                    <div class="logo-container">
                        <img src="{{ asset('images/logo.png') }}" alt="logo">
                    </div>
                    <div class="flex flex-column gap-2 mt-3">
                        <label for="username">Username</label>
                        <input-text class="w-full" v-model="username" autocomplete="username" input-id="username"
                            :required="true" />
                    </div>
                    <div class="flex flex-column gap-2 mt-3">
                        <label for="password">Password</label>
                        <p-password class="w-full" input-class="w-full" toggle-mask v-model="password" input-id="password"
                            :required="true" :feedback="false" />
                    </div>
                    <div class="flex flex-row-reverse justify-content-end mt-3">
                        <label class="text-color-secondary" for="remember">Remember Me </label>
                        <checkbox binary v-model="remember" input-id="remember" />
                    </div>
                    <div class="flex w-full mt-3">
                        <div class="w-full">
                            <p-button type="submit" class="w-full" label="{{ __('Login') }}"></p-button>
                        </div>
                        <div class="w-full">
                            <p-button type="button" severity="primary" class="w-full" outlined label="{{ __('Register') }}"
                                @click="redirect('/register')"></p-button>
                        </div>
                    </div>
                    <div class="flex justify-content-end w-full mt-2">
                        @if (Route::has('password.request'))
                            <a class="btn btn-link text-primary" href="{{ route('password.request') }}">
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
