@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Lapu navigācija">
        <div class="pagination-summary">
            Rāda <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
            no <strong>{{ $paginator->total() }}</strong> ierakstiem
        </div>

        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-step is-disabled" aria-disabled="true">‹ Iepriekšējā</span>
            @else
                <a class="pagination-step" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Iepriekšējā</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-number" aria-current="page" aria-label="Lapa {{ $page }}">{{ $page }}</span>
                        @else
                            <a class="pagination-number" href="{{ $url }}" aria-label="Lapa {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-step" href="{{ $paginator->nextPageUrl() }}" rel="next">Nākamā ›</a>
            @else
                <span class="pagination-step is-disabled" aria-disabled="true">Nākamā ›</span>
            @endif
        </div>
    </nav>
@endif
