<div>
    @if($totalUnreadCount > 0)
        <a
            wire:click="markAllArticlesAsRead"
            @click="
                document.querySelectorAll('.article').forEach(el => {
                    Alpine.evaluate(el, 'localIsRead = true')
                });
            "
            href="javascript:"
            class="ml-3 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
            title="{{ __('Mark all articles as read') }}"
        >
            {{ $totalUnreadCount }}  {{ __('unread') }}
        </a>
    @endif
</div>