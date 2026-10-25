import './bootstrap';

// import hljs from 'highlight.js/lib/core';
// import sql from 'highlight.js/lib/languages/sql';

// // Register the SQL language
// hljs.registerLanguage('sql', sql);

// // Initialize Highlight.js for all code blocks
// document.addEventListener('DOMContentLoaded', () => {
//   document.querySelectorAll('pre code').forEach((block) => {
//       hljs.highlightElement(block);
//   });
// });

// Alpine.js is automatically handled by Livewire 3
// No need to import or initialize it manually

// Import the ecotox modal component
import './alpine-components/ecotox-modal.js';

// Import the CRED evaluation modal component
import './alpine-components/cred-evaluation-modal.js';

// Import the Empodat modal component
import './alpine-components/empodat-modal.js';

// Import the search parameters collapse component
import './search-parameters-collapse.js';

// Register the components when Livewire starts Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('ecotoxModal', window.ecotoxModal);
    Alpine.data('credEvaluationModal', window.credEvaluationModal);
    Alpine.data('empodatModal', window.empodatModal);
});