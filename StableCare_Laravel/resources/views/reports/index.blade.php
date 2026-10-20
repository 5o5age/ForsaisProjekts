@extends('layouts.app')
@section('title', 'Atskaites')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">DATI UN STATISTIKA</div><h1>Atskaites</h1><p class="page-description">Staļļa aktivitāšu pārskats izvēlētajā periodā.</p></div>
    <a class="btn btn-primary" href="{{ route('reports.tasks.csv', ['from_date' => $from->toDateString(), 'to_date' => $to->toDateString()]) }}">Lejupielādēt CSV ↓</a>
</div>
<form method="get" class="filter-bar">
    <div class="field"><label for="from_date">No datuma</label><input type="date" id="from_date" name="from_date" value="{{ $from->toDateString() }}" required></div>
    <div class="field"><label for="to_date">Līdz datumam</label><input type="date" id="to_date" name="to_date" value="{{ $to->toDateString() }}" required></div>
    <button class="btn btn-secondary" type="submit">Atjaunot atskaiti</button>
</form>
<div class="stats-grid">
    <div class="stat-card"><span class="stat-label">Paveikti uzdevumi</span><strong class="stat-number">{{ $completedCount }}</strong><span class="stat-caption">Izvēlētajā periodā</span></div>
    <div class="stat-card"><span class="stat-label">Plānotie uzdevumi</span><strong class="stat-number">{{ $plannedCount }}</strong><span class="stat-caption">Vēl nav atzīmēti kā paveikti</span></div>
    <div class="stat-card"><span class="stat-label">Veselības ieraksti</span><strong class="stat-number">{{ $healthCount }}</strong><span class="stat-caption">Apskates un citas darbības</span></div>
    <div class="stat-card"><span class="stat-label">Treniņi</span><strong class="stat-number">{{ $trainingCount }}</strong><span class="stat-caption">Reģistrētie treniņi</span></div>
    <div class="stat-card"><span class="stat-label">Krājumu kustības</span><strong class="stat-number">{{ $stockMovementCount }}</strong><span class="stat-caption">Pirkumi un patēriņš</span></div>
    <div class="stat-card stat-card-warn"><span class="stat-label">Jāpapildina</span><strong class="stat-number">{{ $lowStockCount }}</strong><span class="stat-caption">Noliktavas vienības</span></div>
</div>
<div class="section-heading"><div><h2>Uzdevumu izpilde</h2><p>Atkārtojošie uzdevumi atskaitē uzskaitīti katram datumam atsevišķi.</p></div></div>
@if($rows->isEmpty())
    <div class="empty-state"><div class="empty-mark">▦</div><h2>Šajā periodā nav uzdevumu</h2><p>Izvēlies citu periodu vai vispirms pievieno uzdevumus aprūpes kalendārā.</p></div>
@else
<div class="data-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Datums / laiks</th><th>Zirgs</th><th>Uzdevums</th><th>Atbildīgais</th><th>Statuss</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ \Carbon\CarbonImmutable::parse($row['date'])->format('d.m.Y') }}<small class="table-sub">{{ $row['task']->task_time ? substr((string)$row['task']->task_time,0,5) : 'Laiks nav norādīts' }}</small></td><td>{{ $row['task']->horse?->name ?? '—' }}</td><td><strong>{{ $row['task']->title }}</strong><small class="table-sub">{{ \App\Models\Task::TASK_TYPES[$row['task']->task_type] ?? $row['task']->task_type }}</small></td><td>{{ $row['task']->assignee?->name ?? '—' }}</td><td><span class="pill {{ $row['done'] ? 'pill-green' : 'pill-gold' }}">{{ $row['done'] ? 'Paveikts' : 'Plānots' }}</span></td></tr>@endforeach
</tbody></table></div></div>
@endif
@endsection
