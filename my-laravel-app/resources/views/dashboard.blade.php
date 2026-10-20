@extends('layouts.auth')

@section('title', 'Mans konts')

@section('content')
    <h1>Sveiks, {{ auth()->user()->name }} {{ auth()->user()->surname }}!</h1>
    <p class="intro">Tu esi veiksmīgi pieslēdzies.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Iziet no konta</button>
    </form>
@endsection