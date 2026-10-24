@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 border-b-2 border-lime-500 text-sm font-semibold leading-5 text-gray-900 bg-lime-50 rounded-t-md shadow-xs focus:outline-hidden focus:border-lime-600 focus:ring-2 focus:ring-lime-200 transition duration-200 ease-in-out'
            : 'inline-flex items-center px-3 py-2 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-700 hover:text-gray-900 hover:bg-gray-100 hover:border-gray-400 rounded-t-md focus:outline-hidden focus:text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-200 transition duration-200 ease-in-out cursor-pointer';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
