# Feedgets

**Feedgets** is a modern, open-source dashboard built as a response to the shutdown of **Netvibes**. Like many, I relied on Netvibes to organize feeds and widgets in one customizable place — but when it announced its shutdown, I couldn't find a suitable replacement. So I built one.

This project is not just a replacement — it's also an AI experiment and playground for me. Wherever possible, I tried to use AI tools and prompt engineering to assist in generating code and tests, scaffolding features, and improving developer workflows.

Feedgets is powered by the TALL stack – install it as a regular Laravel project. Cloudflare's Turnstile is used as a Captcha service.

Contributions are welcome, and feel free to explore how AI can be integrated into modern open-source development.

Some features that are yet to be implemented:
- Caching
- Optimizing Livewire components to work with less data per request
- Replace widget polling with event listeners