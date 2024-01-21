@extends('layouts.app')

@section('content')
    <div id="app">
        <router-view></router-view>
    </div>
@endsection

@section('js')
    @vite(['resources/js/inventory/index.js'])
@endsection
