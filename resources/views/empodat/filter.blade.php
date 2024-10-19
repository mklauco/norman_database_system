<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-prose mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
      
      <div class="p-6 text-gray-900">
        
        
        
        <!-- Main Search form -->
        <div class="grid grid-cols-1 gap-5">
        
        <div id="searchOptions">
          <div class="bg-gray-100 p-2">
            <div class="font-bold mb-2">
              Search options:
            </div>
            <div class="flex items-center space-x-4">
              <label class="inline-flex items-center">
                <input type="radio" class="form-radio text-indigo-600" name="searchOption" value="option1">
                <span class="ml-2"><strong>AND</strong> condition to all criteria</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" class="form-radio text-indigo-600" name="searchOption" value="option2">
                <span class="ml-2"><strong>OR</strong> conditions to all criteria</span>
              </label>
            </div>
          </div>
        </div>

        <div id="searchGeography">
          <div class="bg-gray-100 p-2">
            <div class="font-bold mb-2">
              Geography criteria:
            </div>
            
          </div>
        </div>

        <div id="searchSubstance">
          <div class="bg-gray-100 p-2">
            <div class="font-bold mb-2">
              Substance criteria:
            </div>
            
          </div>
        </div>
        
        
        
        
        
                
        
        <!-- Main Search form -->
        
        </div>        
      </div>
      </div>
    </div>
  </x-app-layout>