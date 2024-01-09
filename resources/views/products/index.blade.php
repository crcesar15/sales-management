@extends('layouts.app')

@section('content')
    <div id="app">
        <the-navbar></the-navbar>
        <products-container></products-container>
    </div>
@endsection

@section('js')
    @vite(['resources/js/products/index.js'])
@endsection
