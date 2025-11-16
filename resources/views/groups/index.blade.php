@extends('layouts.app')

@section('title', 'Search Groups')

@section('content')
<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 z-50 flex items-center justify-center">
    <div class="text-center">
        <div class="relative inline-block">
            <!-- Circular Dot Spinner -->
            <svg class="w-24 h-24 spinner-container" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <!-- 12 dots arranged in a circle -->
                <circle class="spinner-dot" cx="50" cy="10" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="75" cy="18.66" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="91.34" cy="35" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="90" cy="50" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="81.34" cy="65" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="65" cy="81.34" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="50" cy="90" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="35" cy="81.34" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="18.66" cy="65" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="10" cy="50" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="18.66" cy="35" r="4" fill="#3b82f6"/>
                <circle class="spinner-dot" cx="35" cy="18.66" r="4" fill="#3b82f6"/>
            </svg>
        </div>
        <p class="mt-4 text-lg font-medium text-white">Searching groups...</p>
        <p class="mt-2 text-sm text-gray-200">Please wait while we fetch the group members</p>
    </div>
</div>

<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Search Groups</h1>
            <p class="mt-2 text-sm text-gray-700">Enter a Google Workspace Group email address to view all members</p>
        </div>
    </div>

    <div class="mt-8">
        <div class="bg-white shadow rounded-lg p-6">
            <form id="search-form" action="{{ route('groups.search') }}" method="POST">
                @csrf
                <div>
                    <label for="group_email" class="block text-sm font-medium text-gray-700">Group Email Address</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="text" name="group_email" id="group_email" required
                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-l-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="all-elementary-staff or all-elementary-staff@domain.com"
                            value="{{ old('group_email') }}">
                        <button type="submit" id="search-button"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-r-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span id="button-text">Search</span>
                            <svg id="button-spinner" class="hidden ml-2 w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        @if(config('services.email_domain'))
                            You can enter just the group name (e.g., "all-elementary-staff") or the full email address. The domain "@{{ config('services.email_domain') }}" will be added automatically if omitted.
                        @else
                            Enter the full group email address (e.g., "all-elementary-staff@domain.com")
                        @endif
                    </p>
                    @error('group_email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
    
    @keyframes fade {
        0%, 100% {
            opacity: 0.1;
        }
        50% {
            opacity: 1;
        }
    }
    
    .spinner-container {
        animation: spin 1.33s linear infinite;
        transform-origin: 50px 50px;
    }
    
    /* Stagger the opacity animation for each dot to create the wave effect */
    .spinner-dot {
        animation: fade 1.33s ease-in-out infinite;
    }
    
    .spinner-dot:nth-child(1) { animation-delay: 0s; }
    .spinner-dot:nth-child(2) { animation-delay: -0.11s; }
    .spinner-dot:nth-child(3) { animation-delay: -0.22s; }
    .spinner-dot:nth-child(4) { animation-delay: -0.33s; }
    .spinner-dot:nth-child(5) { animation-delay: -0.44s; }
    .spinner-dot:nth-child(6) { animation-delay: -0.55s; }
    .spinner-dot:nth-child(7) { animation-delay: -0.66s; }
    .spinner-dot:nth-child(8) { animation-delay: -0.77s; }
    .spinner-dot:nth-child(9) { animation-delay: -0.88s; }
    .spinner-dot:nth-child(10) { animation-delay: -0.99s; }
    .spinner-dot:nth-child(11) { animation-delay: -1.1s; }
    .spinner-dot:nth-child(12) { animation-delay: -1.21s; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('search-form');
        const loadingOverlay = document.getElementById('loading-overlay');
        const searchButton = document.getElementById('search-button');
        const buttonText = document.getElementById('button-text');
        const buttonSpinner = document.getElementById('button-spinner');
        
        form.addEventListener('submit', function(e) {
            // Show loading overlay
            loadingOverlay.classList.remove('hidden');
            
            // Disable button and show spinner
            searchButton.disabled = true;
            buttonText.textContent = 'Searching...';
            buttonSpinner.classList.remove('hidden');
        });
        
        // Hide loading overlay if page is loaded (in case of redirect back with error)
        // The overlay will be hidden when the new page loads
        if (document.readyState === 'complete') {
            loadingOverlay.classList.add('hidden');
        }
    });
</script>
@endsection

