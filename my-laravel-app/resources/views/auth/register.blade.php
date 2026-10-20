@extends('layouts.auth')

@section('title', 'Reģistrācija')

@section('content')
    <h1>Izveido kontu</h1>
    <p class="intro">Reģistrējies, lai piekļūtu savam kontam.</p>

    @if ($errors->any())
        <ul class="errors" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="field">
            <label for="name">Vārds</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="30" autocomplete="given-name" required autofocus>
        </div>
        <div class="field">
            <label for="surname">Uzvārds</label>
            <input id="surname" name="surname" type="text" value="{{ old('surname') }}" maxlength="30" autocomplete="family-name" required>
        </div>
        <div class="field">
            <label for="email">E-pasts</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="50" autocomplete="email" required>
        </div>
        <div class="field">
            <label for="password">Parole</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" aria-describedby="password-hint" required>
            <small id="password-hint">Vismaz 8 simboli, tajā skaitā burti un cipari.</small>
        </div>
        <button type="submit">Reģistrēties</button>
    </form>

    <p class="form-note">Jau ir konts? <a href="{{ route('login') }}">Pieslēgties</a></p>
@endsection