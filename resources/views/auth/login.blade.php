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
    <div class="grid">
        <div class="col-12 md:col-4 md:col-offset-4">
            <div class="surface-100 p-4 shadow-2 border-round w-full">
                <form method="POST" action="{{ route('login') }}">
                    <!--Add logo from public storage-->
                    <div class="logo-container">
                        <img src="{{ asset('images/logo.png') }}" alt="logo">
                    </div>
                    <div class="col">
                        <label for="email">E-Mail</label>
                        <input-text class="w-full" v-model="email" input-id="email" :required="true" />
                    </div>
                    <div class="col">
                        <label for="password">Password</label>
                        <p-password class="w-full" input-class="w-full" toggle-mask v-model="password" input-id="password"
                            :required="true" :feedback="false" />
                    </div>
                    <div class="col flex">
                        <div class="flex">
                            <checkbox class="" :binary="true" v-model="remember" input-id="remember" />
                        </div>
                        <label class="ml-2 text-color-secondary" for="remember">Remember Me</label>
                    </div>
                    <div class="col">
                        <div class="grid">
                            <div class="col-6">
                                <p-button type="button" class="w-full" label="{{ __('Login') }}"
                                    @click="login"></p-button>
                            </div>
                            <div class="col-6">
                                <p-button type="button" severity="primary" class="w-full" outlined
                                    label="{{ __('Register') }}" @click="redirect('/register')"></p-button>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right">
                        @if (Route::has('password.request'))
                            <a class="btn btn-link text-primary" href="{{ route('password.request') }}">
                                {{ __('Forgot Your Password?') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
