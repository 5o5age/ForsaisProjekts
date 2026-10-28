@extends('layouts.app')
@section('title', 'Kalendārs')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">PLĀNS UN GRAFIKS</div><h1>Kalendārs</h1><p class="page-description">Atkārtotie aprūpes darbi un ieplānotie notikumi vienā skatā.</p></div>
    @if(auth()->user()->canManageTasks())<div class="heading-actions"><a class="btn btn-secondary" href="{{ route('tasks.create') }}">＋ Uzdevums</a><a class="btn btn-primary" href="{{ route('feedings.create') }}">＋ Barošanas plāns</a></div>@endif
</div>
<div class="calendar-toolbar"><a class="calendar-nav" href="{{ route('calendar.index', ['month' => $prevMonth]) }}" aria-label="Iepriekšējais mēnesis">←</a><div><h2>{{ $month->locale('lv')->translatedFormat('F Y') }}</h2><a class="calendar-today" href="{{ route('calendar.index', ['month' => now()->format('Y-m')]) }}">Šis mēnesis</a></div><a class="calendar-nav" href="{{ route('calendar.index', ['month' => $nextMonth]) }}" aria-label="Nākamais mēnesis">→</a></div>
<div class="calendar-card">
    <div class="calendar-grid calendar-weekdays">
        @foreach(['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'] as $weekday)<div>{{ $weekday }}</div>@endforeach
    </div>
    <div class="calendar-grid calendar-days">
        @foreach($days as $day)
            <div class="calendar-day {{ $day['inMonth'] ? '' : 'outside-month' }} {{ $day['isToday'] ? 'is-today' : '' }}">
                <div class="day-number"><span>{{ $day['date']->day }}</span>@if($day['isToday'])<small>ŠODIEN</small>@endif</div>
                <div class="day-events">
                    @foreach($day['tasks'] as $eventIndex => $item)
                        <div class="calendar-event {{ $item['done'] ? 'event-done' : '' }} {{ $item['task']->priority === 'urgent' ? 'event-urgent' : '' }}" @if($eventIndex >= 3) data-calendar-extra hidden @endif>
                            <div class="event-time">{{ $item['task']->task_time ? substr($item['task']->task_time, 0, 5) : '—' }}</div>
                            <div class="event-title">{{ $item['task']->title }}</div>
                            @if($item['task']->horse)<div class="event-horse">{{ $item['task']->horse->name }}</div>@endif
                            @if(auth()->user()->canManageTasks())
                                @if($item['displayDate'] <= now()->toDateString())
                                    <form method="post" action="{{ route('tasks.complete', $item['task']) }}" class="calendar-complete" data-complete-note>
                                        @csrf<input type="hidden" name="date" value="{{ $item['date'] }}"><input type="hidden" name="completed" value="{{ $item['done'] ? '0' : '1' }}"><input type="hidden" name="notes" value="">
                                        <button title="{{ $item['done'] ? 'Atcelt izpildi' : 'Atzīmēt paveiktu' }}">{{ $item['done'] ? '✓' : '○' }}</button>
                                    </form>
                                @endif
                                <details class="calendar-move"><summary>Pārvietot</summary>
                                    <form method="post" action="{{ route('tasks.occurrences.move', $item['task']) }}" class="form-stack">
                                        @csrf<input type="hidden" name="date" value="{{ $item['date'] }}">
                                        <label>Jaunais datums<input type="date" name="new_date" min="{{ now()->toDateString() }}" value="{{ max($item['displayDate'], now()->toDateString()) }}" required></label>
                                        <label>Pārvietot<select name="scope" required><option value="single">Tikai šo reizi</option><option value="future">Šo un turpmākās reizes</option></select></label>
                                        <button class="btn btn-secondary" type="submit">Saglabāt datumu</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @endforeach
                    @if($day['tasks']->count() > 3)
                        <button class="calendar-more-toggle" type="button" data-calendar-more data-more-count="{{ $day['tasks']->count() - 3 }}" aria-expanded="false">+ {{ $day['tasks']->count() - 3 }} vēl</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
<div class="calendar-legend"><span><i class="legend-dot legend-green"></i> Plānots</span><span><i class="legend-dot legend-muted"></i> Paveikts</span><span><i class="legend-dot legend-gold"></i> Augsta prioritāte</span></div>
@endsection
