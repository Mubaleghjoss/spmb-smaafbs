@php($paginator->appends(request()->except('page')))

@if ($paginator->hasPages())
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <small class="text-muted">
            Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
        </small>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <label class="d-flex align-items-center gap-2 small text-muted mb-0">
                Tampilkan
                <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href = this.value">
                    @foreach([15, 20, 25, 50, 100] as $size)
                        @php($url = request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]))
                        <option value="{{ $url }}" @selected($paginator->perPage() === $size)>{{ $size }} data</option>
                    @endforeach
                </select>
                per halaman
            </label>

            <nav aria-label="Navigasi halaman">
                <ul class="pagination pagination-sm mb-0 flex-wrap">
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">&laquo; Sebelumnya</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Sebelumnya</a>
                        </li>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Selanjutnya &raquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">Selanjutnya &raquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
@endif
