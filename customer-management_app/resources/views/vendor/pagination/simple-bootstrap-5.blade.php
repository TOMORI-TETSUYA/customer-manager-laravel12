{{--
    ページ送りのリンク (Laravel の bootstrap-5 ビューをプロジェクト側へ複製したもの)

    リンクにマウスを重ねたときに画面左下へURLが出ないよう、
    js/app.js の hideLinkUrlOnHover() が「乗っている間だけ href を外す」
    処理を行う。ここは普通の <a href="..."> のままでよい。

    ※ php artisan vendor:publish --tag=laravel-pagination で再取得すると
      このコメントは消えるが、リンクの書き方自体は元のままなので動作に影響はない。
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{!! __('Pagination Navigation') !!}">
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">{!! __('pagination.previous') !!}</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        {!! __('pagination.previous') !!}
                    </a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{!! __('pagination.next') !!}</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">{!! __('pagination.next') !!}</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
