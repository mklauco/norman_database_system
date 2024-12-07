<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  <title>{{ config('app.name', 'Laravel') }}</title>
  
  <link rel="apple-touch-icon" sizes="180x180" href="{{asset('f/apple-touch-icon.png')}}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{asset('f/favicon-32x32.png')}}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{asset('f/favicon-16x16.png')}}">
  <link rel="manifest" href="{{asset('f/site.webmanifest')}}">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
  <!-- Scripts -->
  
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  
  
  <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
  
  @vite([
  'resources/css/app.css',
  'resources/js/app.js',
  'resources/css/select2-tw.css'
  ])
  
  {{-- <script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js"></script> --}}
  {{-- <link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet"></link> --}}
  @livewireStyles
</head>
<body class="font-sans antialiased">
  <div class="min-h-screen flex flex-col  bg-gray-100">
    @include('layouts.navigation')
    
    <!-- Page Heading -->
    @isset($header)
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{ $header }}
      </div>
    </header>
    @endisset
    
    {{-- @isset($errors) --}}
    <div class="max-w-6xl mx-auto">
      @include('_t.errors')
    </div>
    {{-- @endisset --}}
    
    <!-- Page Content -->
    <main class="flex-grow">
      {{ $slot }}
    </main>
    
    @include('layouts.footer')
  </div>
  {{-- @include('layouts.footer') --}}
  
  
  
  
  <!-- Scripts -->
  
  <script>
    
    function multiselect(config) {
      return {
        items: config.items ?? [],
        
        allItems: null,
        
        selectedItems: null,
        
        search: config.search ?? "",
        
        searchPlaceholder: config.searchPlaceholder ?? "Type here...",
        
        expanded: config.expanded ?? false,
        
        emptyText: config.emptyText ?? "No items found...",
        
        allowDuplicates: config.allowDuplicates ?? false,
        
        size: config.size ?? 4,
        
        itemHeight: config.itemHeight ?? 40,
        
        maxItemChars: config.maxItemChars ?? 50,
        
        maxTagChars: config.maxTagChars ?? 25,
        
        activeIndex: -1,
        
        onInit() {
          // Set the allItems array since we want to filter later on and keep the original (items) array as reference
          this.allItems = [...this.items];
          
          this.$watch("filteredItems", (newValues, oldValues) => {
            // Reset the activeIndex whenever the filteredItems array changes
            if (newValues.length !== oldValues.length) this.activeIndex = -1;
          });
          
          this.$watch("selectedItems", (newValues, oldValues) => {
            if (this.allowDuplicates) return;
            
            // Remove already selected items from the items (allItems) array (if allowDuplicates is false)
            this.allItems = this.items.filter((item, idx, all) =>
            newValues.every((n) => n.value !== item.value)
            );
          });
          
          // Scroll to active element whenever activeIndex changes (if expanded is true and we have a value)
          this.$watch("activeIndex", (newValue, oldValue) => {
            if (
            this.activeIndex == -1 ||
            !this.filteredItems[this.activeIndex] ||
            !this.expanded
            )
            return;
            
            this.scrollToActiveElement();
          });
          
          // Check whether there are selected values or not and set them
          this.selectedItems = this.items
          ? this.items.filter((item) => item.selected)
          : [];
        },
        
        handleBlur(e) {
          // If the current active element (relatedTarget) is a child element of the component itself, return
          // Note: The current active element must have a tabindex attribute set in order to appear as a relatedTarget
          if (this.$el.contains(e.relatedTarget)) {
            return;
          }
          
          this.reset();
        },
        
        reset() {
          // 1) Clear the search value
          this.search = "";
          
          // 2) Close the list
          this.expanded = false;
          
          // 3) Reset the active index
          this.activeIndex = -1;
        },
        
        handleItemClick(item) {
          // 1) Add the item
          this.selectedItems.push(item);
          
          // 2) Reset the search input
          this.search = "";
          
          // 3) Keep the focus on the search input
          this.$refs.searchInput.focus();
        },
        
        selectNextItem() {
          if (!this.filteredItems.length) return;
          
          // Array count starts at 0, so we abstract 1
          if (this.filteredItems.length - 1 == this.activeIndex) {
            return (this.activeIndex = 0);
          }
          
          this.activeIndex++;
        },
        
        selectPrevItem() {
          if (!this.filteredItems.length) return;
          
          if (this.activeIndex == 0 || this.activeIndex == -1)
          return (this.activeIndex = this.filteredItems.length - 1);
          
          this.activeIndex--;
        },
        
        addActiveItem() {
          if (!this.filteredItems[this.activeIndex]) return;
          
          this.selectedItems.push(this.filteredItems[this.activeIndex]);
          
          this.search = "";
        },
        
        scrollToActiveElement() {
            // Remove the first two child elements since they are <template> tags
            const availableListElements = [...this.$refs.listBox.children].slice(
            2,
            -1
            );
            
            // Scroll to active <li> element
              availableListElements[this.activeIndex].scrollIntoView({
                block: "end",
              });
            },
            
            removeElementByIdx(itemIdx) {
              this.selectedItems.splice(itemIdx, 1);
              
              // Focus the input element to keep the blur functionlity
              // otherwise @focusout on the root element will not be triggered
              if (!this.selectedItems.length) this.$refs.searchInput.focus();
            },
            
            shortenedLabel(label, maxChars) {
              return !maxChars || label.length <= maxChars
              ? label
              : `${label.substr(0, maxChars)}...`;
            },
            
            get filteredItems() {
              return this.allItems.filter((item) =>
              item.label.toLowerCase().includes(this.search?.toLowerCase())
              );
            },
            
            get listBoxStyle() {
              // We add 2 since there is border that takes space
              return {
                maxHeight: `${this.size * this.itemHeight + 2}px`,
              };
            },
          };
        }
        
      </script>
      @livewireScripts
    </body>
    </html>
    