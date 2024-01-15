@extends('layouts.app')

@section('content')
    <div id="app">
        <item-list></item-list>
    </div>
@endsection

@section('js')
    @vite(['resources/js/inventory/index.js'])
@endsection
