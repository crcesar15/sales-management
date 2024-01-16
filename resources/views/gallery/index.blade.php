@extends('layouts.app')

@section('content')
    <div id="app">
        <products-gallery></products-gallery>
    </div>
@endsection

@section('js')
    @vite(['resources/js/gallery/index.js'])
@endsection
