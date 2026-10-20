@extends('layouts.app')
@section('title', 'Piekļuve liegta')
@section('content')<div class="empty-state"><div class="empty-mark">403</div><h1>Šī sadaļa nav tavai lomai.</h1><p>Piekļuve ir ierobežota. Ja tev nepieciešamas papildu tiesības, sazinies ar administratoru.</p><a class="btn btn-primary" href="{{ route('dashboard') }}">Atgriezties panelī</a></div>@endsection
