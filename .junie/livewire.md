# Livewire guidelines
- Important: use Livewire v3
- Do not use emit() because that does not exist in Livewire v3. Always use dispatch().

## Component Structure
- Use a single responsibility approach for Livewire components.
- Use PascalCase for component class names (e.g., `NotificationsMenu`).
- Use kebab-case for component view names (e.g., `notifications-menu.blade.php`).
- Use kebab-case for event names, both in JavaScript and PHP.
- Place component views in the `resources/views/livewire`.

## Component Implementation
- Use proper type hints and PHPDoc annotations for properties and methods.
- Use the `mount()` method for component initialization.
- Use computed properties for derived data.
- Use actions for handling user interactions.
- Use validation for form inputs.
- Use proper error handling and feedback.
- Use Annotations.

## JavaScript and Alpine.js
- Use Alpine.js for interactivity in Livewire components.