<div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h1 class="text-2xl font-semibold mb-6">Frequently Asked Questions</h1>
                
                <div class="space-y-4">
                    <div class="border-t pt-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">What is Feedgets?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Feedgets is a personalized RSS feed reader that allows you to subscribe to your favorite websites and blogs, 
                            organize them in a customizable dashboard, and read their content in a clean, distraction-free interface.
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">How do I add a new feed?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            To add a new feed, navigate to the "Manage Feeds" page by clicking on the link in the navigation menu. 
                            There, you can enter the URL of the RSS feed you want to subscribe to and click "Add Feed".
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">How do I organize my feeds?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            On the dashboard, you can drag and drop feed widgets to rearrange them. Feeds are organized in three columns, 
                            and you can move them between columns or change their order within a column.
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">How do I mark articles as read?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            When you read an article, it is automatically marked as read. You can also mark all articles in a feed as read 
                            by clicking the "X unread" button in the feed widget header or marking all the unread articles on the page by clicking the "X unread" button in the page header.
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">How often are feeds refreshed?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Feeds are automatically refreshed periodically to check for new content. You can also manually refresh a feed 
                            by clicking the refresh button in the feed widget.
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">Can I change between light and dark mode?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Yes, Feedgets supports both light and dark modes. You can switch between them using the theme switcher in the navigation menu. 
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">Is there a limit to how many feeds I can add?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            There is no hard limit on the number of feeds you can add, but for optimal performance, we recommend keeping the number 
                            of feeds reasonable based on your reading habits.
                        </p>
                    </div>
                    
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">How do I delete a feed?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            To delete a feed, go to the "Manage Feeds" page, find the feed you want to delete, and click the delete button. 
                            This will remove the feed and all its articles from your account.
                        </p>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">Can I import feeds from another similar service?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Yes, you can import your feeds in OPML format on the "Manage Feeds" page.
                        </p>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">Can I export my feeds?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Yes, you can export your feeds in OPML format from the "Manage Feeds" page. This allows you to backup your feed
                            subscriptions or import them into another RSS reader.
                        </p>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h2 class="text-xl font-medium mb-2">I have a question. Can I contact you?</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Yes, go to the <a href="{{ route('contact') }}" class="underline" wire:navigate>Contact</a> page to send us a message. We will get back to you as soon as possible.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-x-1 text-center text-sm">
                @include('footer-links')
            </div>
        </div>
    </div>
</div>