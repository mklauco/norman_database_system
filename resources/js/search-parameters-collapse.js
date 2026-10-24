/**
 * Search Parameters Collapsible Component
 * Handles collapsible display of search parameters with large arrays
 */
class SearchParametersCollapse {
    constructor() {
        this.maxVisibleItems = 5; // Show first 5 items by default
        this.init();
    }

    init() {
        this.setupCollapsibleElements();
    }

    setupCollapsibleElements() {
        const parameterSections = document.querySelectorAll('[data-search-parameter]');
        
        parameterSections.forEach(section => {
            const itemsList = section.querySelector('[data-parameter-items]');
            const items = itemsList.querySelectorAll('[data-parameter-item]');
            
            if (items.length > this.maxVisibleItems) {
                this.makeCollapsible(section, itemsList, items);
            }
        });
    }

    makeCollapsible(section, itemsList, items) {
        const totalItems = items.length;
        const hiddenCount = totalItems - this.maxVisibleItems;
        
        // Hide items beyond the max visible
        for (let i = this.maxVisibleItems; i < totalItems; i++) {
            items[i].style.display = 'none';
            items[i].classList.add('hidden-item');
        }

        // Create toggle button
        const toggleButton = this.createToggleButton(hiddenCount, false);
        
        // Insert toggle button after the items list
        itemsList.insertAdjacentElement('afterend', toggleButton);

        // Add click event listener
        toggleButton.addEventListener('click', () => {
            const isExpanded = section.dataset.expanded === 'true';
            this.toggleItems(section, items, toggleButton, !isExpanded);
        });
    }

    createToggleButton(hiddenCount, isExpanded) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'inline-flex items-center text-sm text-gray-600 hover:text-gray-800 ml-2 focus:outline-hidden focus:ring-2 focus:ring-slate-500 focus:ring-offset-1 rounded-sm';
        
        const text = isExpanded ? 'Show less' : `... and ${hiddenCount} more`;
        const iconPath = isExpanded 
            ? 'M5 15l7-7 7 7' // chevron up
            : 'M19 9l-7 7-7-7'; // chevron down
        
        button.innerHTML = `
            <span class="mr-1">${text}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
            </svg>
        `;
        
        button.dataset.toggleButton = 'true';
        return button;
    }

    toggleItems(section, items, toggleButton, expand) {
        const hiddenItems = Array.from(items).slice(this.maxVisibleItems);
        const totalItems = items.length;
        const hiddenCount = totalItems - this.maxVisibleItems;

        if (expand) {
            // Show all hidden items
            hiddenItems.forEach(item => {
                item.style.display = 'inline';
                item.classList.remove('hidden-item');
            });
            section.dataset.expanded = 'true';
        } else {
            // Hide items beyond max visible
            hiddenItems.forEach(item => {
                item.style.display = 'none';
                item.classList.add('hidden-item');
            });
            section.dataset.expanded = 'false';
        }

        // Update button text and icon
        const text = expand ? 'Show less' : `... and ${hiddenCount} more`;
        const iconPath = expand 
            ? 'M5 15l7-7 7 7' // chevron up
            : 'M19 9l-7 7-7-7'; // chevron down

        toggleButton.innerHTML = `
            <span class="mr-1">${text}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
            </svg>
        `;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SearchParametersCollapse();
});
