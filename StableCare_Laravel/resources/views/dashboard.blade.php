@extends('layouts.app')
@section('title', 'Pārskats')
@section('content')
<div class="dashboard-welcome"><div><div class="eyebrow"><span class="eyebrow-line"></span> STABLECARE / PĀRSKATS</div><h1>Labdien, {{ explode(' ', auth()->user()->name)[0] }}.</h1><p class="page-description">{{ now()->locale('lv')->translatedFormat('l, j. F Y') }} · Šeit redzams šodienas svarīgākais.</p></div><div class="welcome-mark">S<span>STABLECARE</span></div></div>
<div class="stats-grid">
    <a href="{{ route('horses.index') }}" class="stat-card stat-link"><span class="stat-top"><span class="stat-label">Zirgi uzskaitē</span><span class="stat-icon">♘</span></span><strong class="stat-number">{{ $horseCount }}</strong><span class="stat-caption">Visi reģistrētie zirgi <b>↗</b></span></a>
    @if(auth()->user()->canManageTasks())
        <a href="{{ route('tasks.index') }}" class="stat-card stat-link"><span class="stat-top"><span class="stat-label">Šodienas uzdevumi</span><span class="stat-icon">✓</span></span><strong class="stat-number">{{ $todayTaskCount }}</strong><span class="stat-caption">{{ $doneCount }} paveikti <b>↗</b></span></a>
    @endif
    @if(auth()->user()->canManageTraining())
        <a href="{{ route('trainings.index') }}" class="stat-card stat-link"><span class="stat-top"><span class="stat-label">Treniņi</span><span class="stat-icon">↗</span></span><strong class="stat-number">{{ $upcomingTraining->count() }}</strong><span class="stat-caption">Tuvākie plānotie <b>↗</b></span></a>
    @endif
    @if(auth()->user()->canManageHealth())
        <a href="{{ route('health.index') }}" class="stat-card stat-link"><span class="stat-top"><span class="stat-label">Veselības atgādinājumi</span><span class="stat-icon">＋</span></span><strong class="stat-number">{{ $healthDue->count() }}</strong><span class="stat-caption">Tuvākās 30 dienas <b>↗</b></span></a>
    @endif
</div>
<div class="dashboard-grid">
    <section class="panel-card panel-main">
        <div class="panel-heading"><div><div class="eyebrow">DIENAS PLĀNS</div><h2>@if(auth()->user()->canManageTasks())Šodienas uzdevumi @elseif(auth()->user()->canManageTraining())Tuvākie treniņi @else Noderīgas sadaļas @endif</h2></div>
            @if(auth()->user()->canManageTasks())<a class="small-link" href="{{ route('tasks.index') }}">Visi uzdevumi ↗</a>@elseif(auth()->user()->canManageTraining())<a class="small-link" href="{{ route('trainings.index') }}">Visi treniņi ↗</a>@endif
        </div>
        @if(auth()->user()->canManageTasks())
            @if($todayTasks->count())
                <div class="task-list">
                    @foreach($todayTasks as $task)
                        @php($done = $task->isCompleteOn($task->occurrence_date))
                        <div class="task-list-item"><form method="post" action="{{ route('tasks.complete', $task) }}" data-complete-note>@csrf<input type="hidden" name="date" value="{{ $task->occurrence_date }}"><input type="hidden" name="completed" value="{{ $done ? '0' : '1' }}"><input type="hidden" name="notes" value=""><button class="check-button {{ $done ? 'checked' : '' }}">{{ $done ? '✓' : '○' }}</button></form><div class="task-list-copy"><strong class="{{ $done ? 'task-completed' : '' }}">{{ $task->title }}</strong><small>{{ $task->horse?->name ?: 'Visam stallim' }} @if($task->task_time) · {{ substr($task->task_time, 0, 5) }} @endif</small></div><span class="pill {{ $task->priority === 'urgent' ? 'pill-gold' : 'pill-gray' }}">{{ ['low'=>'Zema','normal'=>'Parasta','high'=>'Augsta','urgent'=>'Steidzama'][$task->priority] ?? $task->priority }}</span></div>
                    @endforeach
                </div>
            @else
                <div class="inline-empty"><span>✓</span><strong>Šodien nav plānotu uzdevumu.</strong><p>Izmanto šo laiku gudri — parūpējies par zirgiem bez steigas.</p><a href="{{ route('tasks.create') }}" class="small-link">Pievienot uzdevumu ↗</a></div>
            @endif
        @elseif(auth()->user()->canManageTraining())
            @if($upcomingTraining->count())
                <div class="task-list">
                    @foreach($upcomingTraining as $training)
                        <div class="task-list-item"><span class="date-tile"><b>{{ $training->training_date->format('d') }}</b><small>{{ $training->training_date->translatedFormat('M') }}</small></span><div class="task-list-copy"><strong>{{ $training->horse->name }} · {{ $training->training_type }}</strong><small>{{ $training->training_time ? substr($training->training_time, 0, 5) : 'Laiks nav noteikts' }} · {{ $training->trainer?->name ?: 'Treneris nav norādīts' }}</small></div><span class="pill pill-gold">{{ $training->status === 'completed' ? 'Pabeigts' : 'Plānots' }}</span></div>
                    @endforeach
                </div>
            @else
                <div class="inline-empty"><span>↗</span><strong>Treniņu nav.</strong><p>Ieplāno pirmo nodarbību un sāc veidot treniņu vēsturi.</p><a href="{{ route('trainings.create') }}" class="small-link">Plānot treniņu ↗</a></div>
            @endif
        @else
            <div class="quick-links"><a href="{{ route('health.index') }}"><span class="stat-icon">＋</span><strong>Veselības ieraksti</strong><small>Apskates un vakcinācijas</small></a><a href="{{ route('trainings.index') }}"><span class="stat-icon">↗</span><strong>Treniņu žurnāls</strong><small>Nodarbību informācija</small></a><a href="{{ route('calendar.index') }}"><span class="stat-icon">▦</span><strong>Kalendārs</strong><small>Plānotie notikumi</small></a></div>
        @endif
    </section>
    <aside class="dashboard-aside">
        <section class="panel-card"><div class="panel-heading"><div><div class="eyebrow">ĀTRĀ PIEKĻUVE</div><h2>Darba vieta</h2></div></div><div class="quick-action-list"><a href="{{ route('horses.index') }}"><span>♘</span><div><strong>Zirgi</strong><small>Profili un uzskaite</small></div><b>↗</b></a><a href="{{ route('calendar.index') }}"><span>▦</span><div><strong>Kalendārs</strong><small>Grafiki un notikumi</small></div><b>↗</b></a>
            @if(auth()->user()->canManageTasks())<a href="{{ route('feedings.index') }}"><span>◌</span><div><strong>Barošana</strong><small>Aktīvie plāni</small></div><b>↗</b></a>@endif
            @if(auth()->user()->canManageTraining())<a href="{{ route('trainings.index') }}"><span>↗</span><div><strong>Treniņi</strong><small>Plāni un rezultāti</small></div><b>↗</b></a>@endif
            @if(auth()->user()->canManageHealth())<a href="{{ route('health.index') }}"><span>＋</span><div><strong>Veselība</strong><small>Medicīniskā vēsture</small></div><b>↗</b></a>@endif
        </div></section>
        @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
            <section class="panel-card stock-panel"><div class="panel-heading"><div><div class="eyebrow">RESURSI</div><h2>Krājumu statuss</h2></div><a class="small-link" href="{{ route('inventory.index') }}">Visi ↗</a></div>
                @if($lowStock->count())<div class="stock-list">
                    @foreach($lowStock as $item)<div class="stock-item"><span class="stock-mark">!</span><div><strong>{{ $item->name }}</strong><small>{{ number_format((float) $item->quantity, 2, ',', ' ') }} {{ $item->unit }} atlikumā</small></div><span class="pill pill-gold">Zems</span></div>@endforeach
                </div>@else<div class="stock-clear"><span>✓</span><strong>Nav zema krājuma brīdinājumu.</strong><small>Kad atlikums nokritīsies zem minimuma, brīdinājums parādīsies šeit.</small></div>@endif
                @if($expiringStock->count())<div class="upcoming-list stock-expiry-list"><strong class="field-hint">Derīguma termiņš beidzies vai tuvojas (30 dienās)</strong>
                    @foreach($expiringStock as $item)<div><span class="upcoming-date">{{ $item->expires_at->format('d.m') }}</span><span><strong>{{ $item->name }}</strong><small>Derīguma termiņš {{ $item->expires_at->format('d.m.Y') }}</small></span></div>@endforeach
                </div>@endif
            </section>
        @endif
        @if(auth()->user()->canManageHealth())
            <section class="panel-card"><div class="panel-heading"><div><div class="eyebrow">VESELĪBA</div><h2>Nākamās vizītes</h2></div><a class="small-link" href="{{ route('health.index') }}">Visas ↗</a></div>
                @if($healthDue->count())<div class="upcoming-list">
                    @foreach($healthDue as $record)<div><span class="upcoming-date">{{ $record->next_date->format('d.m') }}</span><span><strong>{{ $record->horse->name }}</strong><small>{{ $record->title }}</small></span></div>@endforeach
                </div>@else<p class="muted-paragraph">Šobrīd nav ieplānotu vizīšu tuvāko 30 dienu laikā.</p>@endif
            </section>
        @endif
    </aside>
</div>
@endsection
