// Documentation System - Dynamic Content Loading and Management

// Page content is embedded in index.html as <script type="text/html" id="page-{filename}"> tags.
// To add a new page: add one of those script tags to index.html (copying from docs/pages/),
// then add the page to getNavigation() below.

class DocumentationApp {
    constructor() {
        this.searchQuery = '';
        this.searchResults = [];
        this.mobileMenuOpen = false;
        this.currentPage = this.getCurrentPage();
        this.navigation = this.getNavigation();
        this.pages = this.loadPages();
        this.contentLoaded = false;
    }

    // Get current page from URL hash (hash navigation avoids file:// cross-origin restrictions)
    getCurrentPage() {
        const hash = window.location.hash.replace('#', '');
        if (!hash || hash === 'index.html') {
            return 'index.html';
        }
        return hash.endsWith('.html') ? hash : hash + '.html';
    }

    // Navigation structure
    getNavigation() {
        return [
            {
                title: 'Getting Started',
                items: [
                    { title: 'Installation', url: 'installation.html' },
                    { title: 'Configuration', url: 'configuration.html' },
                    { title: 'Quick Start', url: 'quick-start.html' }
                ]
            },
            {
                title: 'Core Concepts',
                items: [
                    { title: 'Manageable Models', url: 'manageable-models.html' },
                    { title: 'Authentication', url: 'authentication.html' },
                    { title: 'Permissions', url: 'permissions.html' }
                ]
            },
            {
                title: 'Advanced',
                items: [
                    { title: 'Customization', url: 'customization.html' },
                    { title: 'Extending', url: 'extending.html' },
                    { title: 'API Reference', url: 'api-reference.html' }
                ]
            }
        ];
    }

    // Build pages index by scanning <script type="text/html" id="page-{filename}"> tags
    loadPages() {
        const parser = new DOMParser();
        return Array.from(document.querySelectorAll('script[type="text/html"][id^="page-"]')).map(tag => {
            const filename = tag.id.replace(/^page-/, '');
            const html = tag.innerHTML;
            const doc = parser.parseFromString(html, 'text/html');
            const h1 = doc.querySelector('h1');
            const title = h1 ? h1.textContent.trim() : filename.replace('.html', '');
            const content = doc.body.textContent.replace(/\s+/g, ' ').trim();
            const navUrl = filename === 'home.html' ? 'index.html' : filename;
            return { url: navUrl, file: filename, title, content };
        });
    }

    // Initialize the application
    initApp() {
        this.currentPage = this.getCurrentPage();
        this.loadContent();
        this.setupInterception();
    }

    // Load content from the matching <script type="text/html"> tag
    loadContent() {
        const contentArea = document.getElementById('content-area');
        if (!contentArea) return;

        let contentFile = this.currentPage;

        // If it's index.html, show home content
        if (contentFile === 'index.html' || contentFile === '') {
            contentFile = 'home.html';
        }

        const tag = document.getElementById(`page-${contentFile}`)
            || document.getElementById('page-home.html');
        contentArea.innerHTML = tag ? tag.innerHTML : '';
        this.updateTitle();
        this.contentLoaded = true;
    }

    // Update page title
    updateTitle() {
        const page = this.pages.find(p => p.url === this.currentPage);
        if (page) {
            document.title = `${page.title} - WRLA Documentation`;
        } else {
            document.title = 'WRLA Documentation';
        }
    }

    // Setup link interception for SPA-like behavior
    setupInterception() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.getAttribute('href')) {
                const href = link.getAttribute('href');

                // Check if it's a local documentation link
                if (href.endsWith('.html') && !href.startsWith('http') && !href.startsWith('#')) {
                    e.preventDefault();
                    this.navigateTo(href);
                }
            }
        });

        // Handle browser back/forward via hash changes (works with file:// protocol)
        window.addEventListener('hashchange', () => {
            this.currentPage = this.getCurrentPage();
            this.loadContent();
        });
    }

    // Navigate to a page using hash-based routing (compatible with file:// protocol)
    navigateTo(url) {
        const filename = url.split('/').pop() || 'index.html';
        // Set hash; empty hash for home page keeps the URL clean
        window.location.hash = (filename === 'index.html') ? '' : filename;
        this.currentPage = filename;
        this.loadContent();

        // Close mobile menu if open
        this.mobileMenuOpen = false;

        // Scroll to top
        window.scrollTo(0, 0);
    }

    // Perform search
    performSearch() {
        if (this.searchQuery.length < 2) {
            this.searchResults = [];
            return;
        }

        const query = this.searchQuery.toLowerCase();
        this.searchResults = this.pages
            .filter(page =>
                page.title.toLowerCase().includes(query) ||
                page.content.toLowerCase().includes(query)
            )
            .map(page => ({
                title: page.title,
                url: page.url,
                preview: page.content.substring(0, 100) + '...'
            }));
    }

    // Check if mobile
    isMobile() {
        return window.innerWidth < 768;
    }
}

// Initialize Alpine.js with our app
document.addEventListener('alpine:init', () => {
    Alpine.data('documentationApp', () => new DocumentationApp());
});

// If Alpine is already initialized, register the component
if (window.Alpine) {
    Alpine.data('documentationApp', () => new DocumentationApp());
}
