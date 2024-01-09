@extends('layouts.app')

@section('content')
    <div id="app">
        <products-container></products-container>
    </div>
@endsection

@section('js')
    @vite(['resources/js/products/index.js'])
    <script>
        //add active class to nav link
        document.getElementById('products').classList.add('active');
    </script>
@endsection
