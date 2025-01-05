import './bootstrap';

import hljs from 'highlight.js/lib/core';
import sql from 'highlight.js/lib/languages/sql';

// Register the SQL language
hljs.registerLanguage('sql', sql);

// Initialize Highlight.js for all code blocks
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('pre code').forEach((block) => {
      hljs.highlightElement(block);
  });
});

// import Alpine from 'alpinejs';

// window.Alpine = Alpine;

// Alpine.start();