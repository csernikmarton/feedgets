<div wire:poll.120s="refresh"
     x-data="{ hideSpinner: function() { setTimeout(() => { $wire.handleRefreshComplete() }, 500); } }"
     x-init="$watch('$wire.isRefreshing', value => { if (value) hideSpinner() })"
     class="widget bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 space-x-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center widget-header">
        <div class="flex grow">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white" title="{{ $feed->title }}">
                <a href="{{ $feed->url }}" target="_blank">{{ $feed->title }}</a>
            </h3>
        </div>

        <div class="flex items-center space-x-2">
            @if(!$isRefreshing)
                <a
                    href="javascript:"
                    wire:click="refresh"
                    title="{{ __('Refresh feed') }}"
                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </a>
            @endif

            @if($isRefreshing)
                <div class="animate-spin h-4 w-4 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
            @endif

            @if($unreadCount > 0)
                <a
                    href="javascript:"
                    wire:click="markAllAsRead($el.closest('.widget').querySelectorAll('.article')[0].dataset.publishedAt)"
                    @click="
                        $el.closest('.widget').querySelectorAll('.article').forEach(el => {
                            Alpine.evaluate(el, 'localIsRead = true')
                        });
                        $el.remove();
                    "
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors text-nowrap"
                    title="{{ __('Mark all as read') }}"
                >
                    {{ $unreadCount }} {{ __('unread') }}
                </a>
            @endif
        </div>
    </div>

    <div
        class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto"
        x-data="{
            init() {
                $el.addEventListener('scroll', () => {
                    if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight) {
                        @this.loadMoreArticles();
                    }
                });
            }
        }"
        wire:key="feed-{{ $feed->uuid }}-articles"
    >
        @forelse($articles as $article)
            <div x-data="{ localIsRead: {{ $article->is_read ? 'true' : 'false' }} }"
                 x-bind:class="localIsRead ? 'px-1 py-1 bg-gray-50 dark:bg-gray-900' : 'px-1 py-1 bg-white dark:bg-gray-800'"
                 wire:key="article-{{ $article->uuid }}"
                 class="article"
                data-published-at="{{ $article->published_at }}">
                <a
                    href="{{ $article->link }}"
                    target="_blank"
                    @click="if(!localIsRead) {
                        localIsRead = true;
                        $wire.markArticleAsRead('{{ $article->uuid }}');
                    }"
                    @auxclick="if ($event.button === 1 && !localIsRead) {
                        localIsRead = true;
                        $wire.markArticleAsRead('{{ $article->uuid }}');
                    }"
                    x-bind:class="localIsRead ? 'text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 block' : 'text-xs font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block'"
                >
                    <div class="flex items-start">
                        <template x-if="!localIsRead">
                            <span class="h-2 w-2 mt-1.5 mr-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                        </template>
                        <span x-bind:class="localIsRead ? 'mr-2 ml-4' : 'mr-2'">{{ $article->title }}</span>
                        <span class="ml-auto text-right text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $article->published_at->diffForHumans() }}</span>
                    </div>
                </a>
            </div>
        @empty
            <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                <p>{{ __('No articles found') }}</p>
            </div>
        @endforelse

        @if($isLoading)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        @elseif(!$hasMoreArticles && count($articles) > 0)
            <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ __('End of articles') }}
            </div>
        @endif
    </div>

    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 text-xs text-gray-500 dark:text-gray-400">
        {{ __('Last updated') }}: {{ $lastRefreshTime->diffForHumans() }}
    </div>
</div>