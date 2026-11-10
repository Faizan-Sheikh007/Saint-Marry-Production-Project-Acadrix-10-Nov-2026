@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">

        <div style="font-size:12px;color:var(--text-muted)">
            Showing <b style="color:var(--text)">{{ $paginator->firstItem() }}</b>
            to <b style="color:var(--text)">{{ $paginator->lastItem() }}</b>
            of <b style="color:var(--text)">{{ $paginator->total() }}</b> results
        </div>

        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);color:var(--text-muted);opacity:.5;cursor:not-allowed">
                    <i class="fas fa-chevron-left" style="font-size:11px"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);color:var(--text);background:var(--card);text-decoration:none" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
                    <i class="fas fa-chevron-left" style="font-size:11px"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)

                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;color:var(--text-muted)">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 6px;border-radius:8px;background:var(--primary);color:#fff;font-weight:600;font-size:13px">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 6px;border-radius:8px;border:1px solid var(--border);color:var(--text);background:var(--card);text-decoration:none;font-size:13px" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);color:var(--text);background:var(--card);text-decoration:none" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
                    <i class="fas fa-chevron-right" style="font-size:11px"></i>
                </a>
            @else
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);color:var(--text-muted);opacity:.5;cursor:not-allowed">
                    <i class="fas fa-chevron-right" style="font-size:11px"></i>
                </span>
            @endif

        </div>
    </nav>
@endif
