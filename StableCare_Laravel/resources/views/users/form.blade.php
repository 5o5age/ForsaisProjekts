@extends('layouts.app')
@section('title', $user->exists ? 'Labot lietotāju' : 'Pievienot lietotāju')
@section('content')
<div class="page-heading"><div><div class="eyebrow">SISTĒMAS PĀRVALDĪBA</div><h1>{{ $user->exists ? 'Labot lietotāju' : 'Pievienot lietotāju' }}</h1><p class="page-description">Konts tiek piesaistīts šim stallim. Administratora loma piešķir plašas tiesības — izmanto to tikai tad, ja nepieciešams.</p></div><a class="btn btn-ghost" href="{{ route('users.index') }}">← Uz lietotājiem</a></div>
<form class="form-card" method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">@csrf @if($user->exists)@method('PUT')@endif
<div class="form-section"><h2>Konta informācija</h2><div class="form-grid">
<div class="field"><label for="first_name">Vārds *</label><input id="first_name" name="first_name" maxlength="30" value="{{ old('first_name',$user->first_name) }}" required></div>
<div class="field"><label for="last_name">Uzvārds *</label><input id="last_name" name="last_name" maxlength="30" value="{{ old('last_name',$user->last_name) }}" required></div>
<div class="field"><label for="email">E-pasts *</label><input id="email" name="email" type="email" maxlength="50" value="{{ old('email',$user->email) }}" required></div>
<div class="field"><label for="phone">Tālrunis</label><input id="phone" name="phone" type="tel" maxlength="40" value="{{ old('phone',$user->phone) }}"></div>
<div class="field"><label for="role">Loma *</label><select id="role" name="role" required>@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected(old('role',$user->role ?: 'supervisor')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="password">{{ $user->exists ? 'Jauna parole (pēc izvēles)' : 'Sākotnējā parole *' }}</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}><span class="field-hint">Vismaz 8 rakstzīmes, burti un cipari. {{ $user->exists ? 'Atstāj tukšu, ja paroli nemaini.' : 'Nodod paroli lietotājam drošā veidā.' }}</span></div>
<div class="field"><label for="password_confirmation">Atkārtot paroli {{ $user->exists ? '(ja to maini)' : '*' }}</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}></div>
</div></div><div class="form-actions"><a class="btn btn-ghost" href="{{ route('users.index') }}">Atcelt</a><button class="btn btn-primary" type="submit">{{ $user->exists ? 'Saglabāt izmaiņas' : 'Izveidot lietotāju' }} ↗</button></div></form>
@endsection
