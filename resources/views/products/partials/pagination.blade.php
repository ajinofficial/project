@php
    $itemLabel = $itemLabel ?? 'products';
    $translatedItemLabel = app()->isLocale('en') ? $itemLabel : __(ucfirst($itemLabel));
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<nav class="pagination-wrap product-pagination" aria-label="{{ __('common.pagination', ['items' => __(ucfirst($itemLabel))]) }}">
    <p>{{ __('common.showing_results', ['first' => number_format($paginator->firstItem()), 'last' => number_format($paginator->lastItem()), 'total' => number_format($paginator->total()), 'items' => $translatedItemLabel]) }}</p>

    <div class="product-page-links">
        @if ($paginator->onFirstPage())
            <span class="product-page-link product-page-arrow is-disabled" aria-disabled="true" aria-label="{{ __('common.previous_page_81f547') }}">&lsaquo;</span>
        @else
            <a class="product-page-link product-page-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('common.previous_page_81f547') }}">&lsaquo;</a>
        @endif

        @if ($startPage > 1)
            <a class="product-page-link" href="{{ $paginator->url(1) }}">1</a>
            @if ($startPage > 2)
                <span class="product-page-gap" aria-hidden="true">...</span>
            @endif
        @endif

        @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $currentPage)
                <span class="product-page-link is-active" aria-current="page">{{ $page }}</span>
            @else
                <a class="product-page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($endPage < $lastPage)
            @if ($endPage < $lastPage - 1)
                <span class="product-page-gap" aria-hidden="true">...</span>
            @endif
            <a class="product-page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="product-page-link product-page-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('common.next_page_4bfc19') }}">&rsaquo;</a>
        @else
            <span class="product-page-link product-page-arrow is-disabled" aria-disabled="true" aria-label="{{ __('common.next_page_4bfc19') }}">&rsaquo;</span>
        @endif
    </div>
</nav>
