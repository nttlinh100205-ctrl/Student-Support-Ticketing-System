<section class="uni-latest-news" data-latest-news data-api="{{ rtrim(config('ui.news'), '/') }}/api/news" aria-label="Tin tức mới nhất">
    <header class="uni-news-heading"><div><span class="uni-news-kicker"><x-nav-icon name="news"/> BẢNG TIN UNISUPPORT</span><h2>Tin tức mới nhất</h2><p>Cập nhật thông báo, lịch học và các hoạt động dành cho sinh viên.</p></div><a class="uni-button secondary" href="{{ config('ui.news') }}">Xem tất cả tin tức →</a></header>
    <div class="uni-news-grid" aria-live="polite"><p class="uni-news-message">Đang tải tin tức mới…</p></div>
    <dialog class="uni-news-dialog"><header><h2></h2><button type="button" aria-label="Đóng tin tức">×</button></header><p class="uni-news-meta"></p><div class="uni-news-body"></div><p><a href="{{ config('ui.news') }}">Mở bảng tin và tài liệu đính kèm →</a></p></dialog>
</section>
<script src="/js/latest-news.js" defer></script>
