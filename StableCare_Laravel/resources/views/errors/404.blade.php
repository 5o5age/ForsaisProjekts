@extends('layouts.app')
@section('title', 'Lapa nav atrasta')
@section('content')<div class="empty-state"><div class="empty-mark">404</div><h1>Šī lapa nav atrasta.</h1><p>Adrese var būt mainījusies vai lapa vairs nepastāv.</p><a class="btn btn-primary" href="{{ route('dashboard') }}">Atgriezties panelī</a></div>@endsection
