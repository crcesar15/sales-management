@extends('layouts.app')

@section('content')
    <div id="app">
        <category-list></category-list>
    </div>
@endsection

@section('js')
    @vite(['resources/js/category/index.js'])
@endsection
