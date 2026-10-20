@extends('layouts.auth')

@section('title', 'Pieslēgšanās')

@section('content')
    <h1>Sveicināts atpakaļ</h1>
    <p class="intro">Ievadi sava konta piekļuves datus.</p>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="errors" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label for="email">E-pasts</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="50" autocomplete="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Parole</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button type="submit">Pieslēgties</button>
    </form>

    <p class="form-note">Nav konta? <a href="{{ route('register') }}">Reģistrēties</a></p>
@endsection