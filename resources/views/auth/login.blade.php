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
    <v-container>
        <v-col cols=12 md=6 offset-md=3>
            <v-card class="mx-auto pa-12 pb-8" elevation="8" max-width="448"rounded="lg">
                <form method="POST" @submit.prevent="login">
                    <!--Add logo from public storage-->
                    <v-row class="logo-container">
                        <img src="{{ asset('images/logo.png') }}" alt="logo">
                    </v-row>
                    <v-row class="d-flex flex-column">
                        <div class="text-subtitle-1 text-medium-emphasis">Username</div>
                        <v-text-field v-model="username" density="compact" prepend-inner-icon="fa-solid fa-user"
                            variant="outlined" />
                    </v-row>
                    <v-row class="d-flex flex-column">
                        <div class="text-subtitle-1 text-medium-emphasis">Password</div>
                        <v-text-field v-model="password" density="compact" prepend-inner-icon="fa-solid fa-key"
                            variant="outlined" :append-inner-icon="password_visible ? 'fa fa-eye' : 'fa fa-eye-slash'"
                            :type="password_visible ? 'text' : 'password'"
                            @click:append-inner="password_visible = !password_visible" />
                    </v-row>
                    <v-row class="d-flex">
                        <checkbox class="" :binary="true" v-model="remember" input-id="remember" />
                        <label class="ml-2 text-color-secondary" for="remember">Remember Me</label>
                    </v-row>
                    <v-row class="grid">
                        <v-col col=6>
                            <v-btn type="submit" block color="primary">{{ __('Login') }}</v-btn>
                        </v-col>
                        <v-col col=6>
                            <v-btn type="button" block @click="redirect('/register')"> {{ __('Register') }}</v-btn>
                        </v-col>
                    </v-row>
                    <div style="text-align: right; margin-top: 10px">
                        @if (Route::has('password.request'))
                            <a class="btn btn-link text-primary" href="{{ route('password.request') }}">
                                {{ __('Forgot Your Password?') }}
                            </a>
                        @endif
                    </div>
                </form>
            </v-card>
        </v-col>
    </v-container>
@endsection
