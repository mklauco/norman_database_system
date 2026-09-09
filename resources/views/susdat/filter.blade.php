<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          <!-- Introductory text -->
          <div class="mb-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
            <p class="text-gray-700 leading-relaxed">
              NORMAN Substance database (SusDat) is a "living database" compiling information provided by NORMAN network members and external contributors via the NORMAN Suspect List Exchange (<a href="https://www.norman-network.com/nds/SLE" target="_blank" class="link-lime-text hover:underline">NORMAN-SLE</a>). NORMAN SusDat merges the many chemical lists on the SLE into a common format and includes all data suitable for screening purposes, along with selected identifiers and predicted values as a service for NORMAN members and beyond. SusDat is undergoing constant development and improvement to expand the coverage, together with contributors and cheminformatics experts. The original lists on the <a href="https://www.norman-network.com/nds/SLE" target="_blank" class="link-lime-text hover:underline">NORMAN-SLE</a> should be consulted to verify chemical information if necessary (see Source column). Comments and contributions are welcome - please email us at <a href="mailto:suspects@normandata.eu" class="link-lime-text hover:underline">suspects@normandata.eu</a>.
            </p>
          </div>
          
          <!-- Main Search form -->
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 xl:gap-8">
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Search Use category:
                </div>
                <form name="searchAccordingToCategoryForm" id="searchAccordingToCategoryForm" action="{{route('substances.search.search')}}" method="GET" onsubmit="return validateCategorySelection()">
                  <input type="hidden" value="1" name="searchCategory">
                  <input type="hidden" value="1" name="search">
                  
                  <!-- Error message for no category selection -->
                  <div id="categoryError" class="hidden mb-3 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <div class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                      </svg>
                      <span class="font-medium">Please select at least one category to continue.</span>
                    </div>
                  </div>
                  
                  <div class="space-y-1 pr-2">
                    @foreach ($categories as $category)
                    <div @if($category->children->isNotEmpty()) x-data="{ open: false }" @endif>
                      <div class="flex items-center space-x-2 p-0.5 rounded hover:bg-gray-100 transition-colors duration-150">
                        @if ($category->children->isNotEmpty())
                        <button type="button" x-on:click="open = !open" :aria-expanded="open" class="shrink-0 w-5 h-5 flex items-center justify-center text-gray-500 hover:text-lime-700" aria-label="Toggle subcategories of {{ $category->name }}">
                          <svg class="w-4 h-4 transition-transform duration-150" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                          </svg>
                        </button>
                        @else
                        <span class="shrink-0 w-5 h-5"></span>
                        @endif
                        <input type="checkbox" name="categoriesSearch[]" value="{{$category->id}}" id="category_{{$category->id}}" class="w-5 h-5 text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
                        <label for="category_{{$category->id}}" class="text-base text-gray-700 cursor-pointer select-none">
                          {!! preg_replace('/\s*\(/', '&nbsp;(', $category->name_abbreviation, 1) !!}
                        </label>
                      </div>

                      @if ($category->children->isNotEmpty())
                      <div x-show="open" x-collapse class="ml-7 border-l border-gray-200 pl-2 space-y-1">
                        @foreach ($category->children as $subcategory)
                        <div @if($subcategory->children->isNotEmpty()) x-data="{ openSub: false }" @endif>
                          <div class="flex items-center space-x-2 p-0.5 rounded hover:bg-gray-100 transition-colors duration-150">
                            @if ($subcategory->children->isNotEmpty())
                            <button type="button" x-on:click="openSub = !openSub" :aria-expanded="openSub" class="shrink-0 w-4 h-4 flex items-center justify-center text-gray-500 hover:text-lime-700" aria-label="Toggle subcategories of {{ $subcategory->name }}">
                              <svg class="w-3 h-3 transition-transform duration-150" :class="openSub ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                              </svg>
                            </button>
                            @else
                            <span class="shrink-0 w-4 h-4"></span>
                            @endif
                            <input type="checkbox" name="categoriesSearch[]" value="{{$subcategory->id}}" id="category_{{$subcategory->id}}" class="w-4 h-4 text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
                            <label for="category_{{$subcategory->id}}" class="text-sm text-gray-700 cursor-pointer select-none">{{ $subcategory->name }}</label>
                          </div>

                          @if ($subcategory->children->isNotEmpty())
                          <div x-show="openSub" x-collapse class="ml-6 border-l border-gray-200 pl-2 space-y-1">
                            @foreach ($subcategory->children as $leaf)
                            <div class="flex items-center space-x-2 p-0.5 rounded hover:bg-gray-100 transition-colors duration-150">
                              <input type="checkbox" name="categoriesSearch[]" value="{{$leaf->id}}" id="category_{{$leaf->id}}" class="w-4 h-4 text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
                              <label for="category_{{$leaf->id}}" class="text-sm text-gray-600 cursor-pointer select-none">{{ $leaf->name }}</label>
                            </div>
                            @endforeach
                          </div>
                          @endif
                        </div>
                        @endforeach
                      </div>
                      @endif
                    </div>
                    @endforeach
                  </div>
                  
                  <div class="mt-4 pt-3 border-t border-gray-200">
                    <button type="submit" class="btn-submit w-full"> Apply Category Filter </button>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Search according to source in SLE:
                </div>
                
                <form name="searchAccordingToSourceForm" id="searchAccordingToSourceForm" action="{{route('substances.search.search')}}" method="GET">
                  <input type="hidden" value="1" name="searchSource">
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'sourcesSearch', 'list' => $sourceList,
                      'active_ids' => isset($request->sourcesSearch) ? $request->sourcesSearch : [],
                      ])
                  </div>
                  
                  <div class="mt-4 pt-3 border-t border-gray-200">
                    <button type="submit" class="btn-submit w-full"> Apply Source Filter</button>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Interactive search for a specific substance:
                </div>                  
                <div>
                  @livewire('susdat.substance-search')
                </div>
              </div>
            </div>              
            
          </div>        
        </div>
      </div>
    </div>
  </div>

  <script>
    function validateCategorySelection() {
      const checkboxes = document.querySelectorAll('input[name="categoriesSearch[]"]');
      const checkedBoxes = Array.from(checkboxes).filter(checkbox => checkbox.checked);
      const errorDiv = document.getElementById('categoryError');
      
      if (checkedBoxes.length === 0) {
        errorDiv.classList.remove('hidden');
        // Scroll to error message
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      } else {
        errorDiv.classList.add('hidden');
        return true;
      }
    }

    // Hide error message when user starts selecting categories
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxes = document.querySelectorAll('input[name="categoriesSearch[]"]');
      const errorDiv = document.getElementById('categoryError');
      
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
          if (checkedBoxes.length > 0) {
            errorDiv.classList.add('hidden');
          }
        });
      });
    });
  </script>
</x-app-layout>
  