<div>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center">
                {{ __('RSS Feed Dashboard') }}
                <livewire:feed-dashboard-total-unread-count />
            </h2>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden md:inline-block">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                    {{ __('Drag widget headers to rearrange') }}
                </span>
                <x-primary-button type="link" href="{{ route('feeds.manage') }}">
                    {{ __('Manage Feeds') }}
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDOUF6qgqqszs+iXU4UA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <div class="py-6"
        x-data="{}"
        x-init="$nextTick(() => { $wire.on('refresh', () => location.reload()); })">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="dashboard-grid">
                @foreach($columns as $columnIndex => $columnFeeds)
                    <div class="widget-column min-h-[100px] bg-gray-50/30 dark:bg-gray-900/10 rounded-lg" data-column="{{ $columnIndex }}">
                        @foreach($columnFeeds as $feed)
                            <div class="widget-container mb-6 rounded-lg hover:ring-2 hover:ring-blue-200 dark:hover:ring-blue-800 transition-all duration-200" data-feed-id="{{ $feed->uuid }}">
                                <livewire:feed-widget :feed="$feed" :key="'feed-'.$feed->uuid" />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($columns[0]->isEmpty() && $columns[1]->isEmpty() && $columns[2]->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">{{ __('No feeds added yet') }}</div>
                        <a href="{{ route('feeds.manage') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-700 transition">
                            {{ __('Add Your First Feed') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            function waitForSortable() {
                if (typeof Sortable !== 'undefined') {
                    // console.log('Sortable.js loaded, version: ' + Sortable.version);
                    initializeSortable();
                } else {
                    // console.log('Waiting for Sortable.js to load...');
                    setTimeout(waitForSortable, 100);
                }
            }

            waitForSortable();

            document.addEventListener('livewire:initialized', function() {
                // console.log('Livewire initialized');
                setTimeout(initializeSortable, 200); // Longer delay to ensure DOM is ready
            });

            document.addEventListener('livewire:update', function() {
                // console.log('Livewire component updated');
                setTimeout(initializeSortable, 200);
            });

            function initializeSortable() {
                // console.log('Initializing Sortable.js');

                document.querySelectorAll('.widget-column').forEach(column => {
                    if (column._sortable) {
                        // console.log(`Destroying existing Sortable instance for column ${column.getAttribute('data-column')}`);
                        column._sortable.destroy();
                        delete column._sortable;
                    }
                });

                const columns = document.querySelectorAll('.widget-column');

                if (columns.length === 0) {
                    // console.log('No columns found for Sortable initialization', { selector: '.widget-column' });
                    return;
                }

                // console.log(`Found ${columns.length} columns`);

                columns.forEach((column, index) => {
                    const columnWidgets = column.querySelectorAll('.widget-container');
                    // console.log(`Column ${index} contains ${columnWidgets.length} widgets`);

                    try {
                        const sortable = new Sortable(column, {
                            group: 'widgets',
                            animation: 150,
                            handle: '.widget-header',
                            ghostClass: 'sortable-ghost',
                            dragClass: 'sortable-drag',

                            onStart: function(evt) {
                                // console.log('Drag started', { index: evt.oldIndex });
                                document.body.style.cursor = 'grabbing';
                            },
                            
                            onEnd: function(evt) {
                                document.body.style.cursor = '';
                                
                                const itemEl = evt.item;
                                const fromEl = evt.from;
                                const toEl = evt.to;
                                
                                // console.log('Drag ended', {
                                //     oldIndex: evt.oldIndex,
                                //     newIndex: evt.newIndex,
                                //     fromColumn: fromEl.getAttribute('data-column'),
                                //     toColumn: toEl.getAttribute('data-column')
                                // });
                                
                                const feedId = itemEl.getAttribute('data-feed-id');
                                const fromColumn = fromEl.getAttribute('data-column');
                                const toColumn = toEl.getAttribute('data-column');
                                
                                if (!feedId) {
                                    // console.log('Error: Missing feed ID on dragged element', {
                                    //     element: itemEl.outerHTML.substring(0, 100) + '...'
                                    // });
                                    return;
                                }
                                
                                const columnWidgets = {};
                                document.querySelectorAll('.widget-column').forEach(col => {
                                    const colIndex = col.getAttribute('data-column');
                                    columnWidgets[colIndex] = Array.from(col.querySelectorAll('.widget-container')).map(w => {
                                        const id = w.getAttribute('data-feed-id');
                                        const titleEl = w.querySelector('.text-sm.font-medium');
                                        const title = titleEl ? titleEl.textContent.trim() : 'Unknown';
                                        return { id, title };
                                    });
                                });
                                
                                // console.log('Updating widget position', {
                                //     feed: {
                                //         id: feedId,
                                //         title: itemEl.querySelector('.text-sm.font-medium')?.textContent.trim() || 'Unknown'
                                //     },
                                //     fromColumn: parseInt(fromColumn),
                                //     toColumn: parseInt(toColumn),
                                //     oldIndex: evt.oldIndex,
                                //     newIndex: evt.newIndex,
                                //     columnWidgetsAfterDrag: columnWidgets
                                // });
                                
                                const widgetEl = document.getElementById(`feed-widget-${feedId}`);
                                if (widgetEl) {
                                    widgetEl.classList.add('opacity-70', 'transition-opacity');
                                }

                                const data = {
                                    feedId: feedId,
                                    fromColumn: parseInt(fromColumn),
                                    toColumn: parseInt(toColumn),
                                    newIndex: parseInt(evt.newIndex)
                                };

                                Livewire.dispatch('update-position', data);

                                Livewire.on('position-updated', (data) => {
                                    // console.log('Position update successful', data);
                                    if (widgetEl) {
                                        widgetEl.classList.remove('opacity-70', 'transition-opacity');
                                    }
                                });

                                Livewire.on('position-update-failed', (data) => {
                                    // console.error('Error updating position:', data);
                                    if (widgetEl) {
                                        widgetEl.classList.remove('opacity-70', 'transition-opacity');
                                    }
                                });
                            }
                        });
                    
                        column._sortable = sortable;
                    } catch (error) {
                        // console.log('Error initializing Sortable instance', {
                        //     columnIndex: index,
                        //     error: error.message
                        // });
                    }
                });
            }
        });
    </script>
</div>