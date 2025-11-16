@extends('layouts.app')

@section('title', 'Group Results')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Group Results</h1>
            <p class="mt-2 text-sm text-gray-700">
                <strong>Group:</strong> {{ $groupInfo['name'] ?? $groupInfo['email'] }}
            </p>
            <p class="mt-1 text-sm text-gray-700">
                <strong>Flattened Member Count:</strong> {{ number_format($memberCount) }}
            </p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <form action="{{ route('groups.download') }}" method="POST">
                @csrf
                <input type="hidden" name="group_email" value="{{ $groupInfo['email'] }}">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Search/Filter Box -->
    <div class="mt-8 mb-4">
        <div class="bg-white shadow rounded-lg p-4">
            <label for="member-search" class="block text-sm font-medium text-gray-700 mb-2">Search Members</label>
            <input type="text" 
                   id="member-search" 
                   placeholder="Search by name, email, title, department, or source group..."
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
            <p class="mt-2 text-sm text-gray-500">
                <span id="filtered-count">{{ $memberCount }}</span> of {{ number_format($memberCount) }} members shown
            </p>
        </div>
    </div>

    <div class="mt-4 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table id="members-table" class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Source Group</th>
                                @if(collect($members)->contains(fn($m) => !empty($m['title']) || !empty($m['department'])))
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Department</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="members-tbody" class="divide-y divide-gray-200 bg-white">
                            @forelse($members as $member)
                            <tr class="member-row even:bg-gray-50 hover:bg-gray-100" 
                                data-name="{{ strtolower($member['name']) }}"
                                data-email="{{ strtolower($member['email']) }}"
                                data-title="{{ strtolower($member['title'] ?? '') }}"
                                data-department="{{ strtolower($member['department'] ?? '') }}"
                                data-source="{{ strtolower($member['source_group']) }}">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    {{ $member['name'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $member['email'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $member['source_group'] }}
                                </td>
                                @if(collect($allMembers)->contains(fn($m) => !empty($m['title']) || !empty($m['department'])))
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $member['title'] ?? '' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $member['department'] ?? '' }}
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">No members found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($paginator->hasPages())
    <div class="mt-6 flex items-center justify-between pagination-container">
        <div class="flex-1 flex justify-between sm:hidden">
            @if($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
            @else
                <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    to
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    of
                    <span class="font-medium">{{ number_format($paginator->total()) }}</span>
                    results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    @if($paginator->onFirstPage())
                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                        @if($page == $paginator->currentPage())
                            <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </nav>
            </div>
        </div>
    </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('groups.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Search
        </a>
    </div>
</div>

<!-- Store all members data for client-side search -->
<script>
const allMembersData = @json($allMembers);
const hasTitleColumn = {{ collect($allMembers)->contains(fn($m) => !empty($m['title']) || !empty($m['department'])) ? 'true' : 'false' }};
const membersPerPage = 50;
let currentPage = {{ $paginator->currentPage() }};
let filteredMembers = [];
let searchTerm = '';

function renderMembers(members, page = 1, isClientSide = false) {
    const tbody = document.getElementById('members-tbody');
    if (!tbody) return;
    
    const offset = (page - 1) * membersPerPage;
    const pageMembers = members.slice(offset, offset + membersPerPage);
    
    tbody.innerHTML = '';
    
    if (pageMembers.length === 0) {
        const colspan = hasTitleColumn ? 5 : 3;
        tbody.innerHTML = '<tr><td colspan="' + colspan + '" class="px-3 py-4 text-sm text-gray-500 text-center">No members found</td></tr>';
        return;
    }
    
    pageMembers.forEach(function(member, index) {
        const row = document.createElement('tr');
        row.className = 'member-row ' + (index % 2 === 0 ? 'even:bg-gray-50' : '') + ' hover:bg-gray-100';
        
        const nameCell = '<td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">' + escapeHtml(member.name) + '</td>';
        const emailCell = '<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">' + escapeHtml(member.email) + '</td>';
        const sourceCell = '<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">' + escapeHtml(member.source_group) + '</td>';
        
        let rowContent = nameCell + emailCell + sourceCell;
        
        if (hasTitleColumn) {
            rowContent += '<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">' + escapeHtml(member.title || '') + '</td>';
            rowContent += '<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">' + escapeHtml(member.department || '') + '</td>';
        }
        
        row.innerHTML = rowContent;
        tbody.appendChild(row);
    });
    
    // Update pagination only for client-side
    if (isClientSide) {
        updateClientPagination(members.length, page);
    }
}

function updateClientPagination(total, page) {
    const totalPages = Math.ceil(total / membersPerPage);
    const paginationContainer = document.querySelector('.pagination-container');
    
    if (!paginationContainer) return;
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    // Hide server pagination, show client pagination
    paginationContainer.style.display = 'flex';
    
    const queryParams = new URLSearchParams(window.location.search);
    const groupEmail = queryParams.get('group_email') || '{{ $groupInfo['email'] }}';
    
    // Update the pagination HTML
    let paginationHTML = '<div class="flex-1 flex justify-between sm:hidden">';
    if (page > 1) {
        paginationHTML += '<a href="#" onclick="goToPage(' + (page - 1) + '); return false;" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>';
    } else {
        paginationHTML += '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">Previous</span>';
    }
    
    if (page < totalPages) {
        paginationHTML += '<a href="#" onclick="goToPage(' + (page + 1) + '); return false;" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>';
    } else {
        paginationHTML += '<span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">Next</span>';
    }
    paginationHTML += '</div>';
    
    paginationHTML += '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    paginationHTML += '<div><p class="text-sm text-gray-700">Showing <span class="font-medium">' + ((page - 1) * membersPerPage + 1) + '</span> to <span class="font-medium">' + Math.min(page * membersPerPage, total) + '</span> of <span class="font-medium">' + total.toLocaleString() + '</span> results</p></div>';
    
    paginationHTML += '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
    
    // Previous button
    if (page > 1) {
        paginationHTML += '<a href="#" onclick="goToPage(' + (page - 1) + '); return false;" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><span class="sr-only">Previous</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></a>';
    } else {
        paginationHTML += '<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed"><span class="sr-only">Previous</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span>';
    }
    
    // Page numbers
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(totalPages, page + 2);
    for (let p = startPage; p <= endPage; p++) {
        if (p === page) {
            paginationHTML += '<span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">' + p + '</span>';
        } else {
            paginationHTML += '<a href="#" onclick="goToPage(' + p + '); return false;" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' + p + '</a>';
        }
    }
    
    // Next button
    if (page < totalPages) {
        paginationHTML += '<a href="#" onclick="goToPage(' + (page + 1) + '); return false;" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><span class="sr-only">Next</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></a>';
    } else {
        paginationHTML += '<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed"><span class="sr-only">Next</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></span>';
    }
    
    paginationHTML += '</nav></div></div>';
    paginationContainer.innerHTML = paginationHTML;
}

function goToPage(page) {
    currentPage = page;
    renderMembers(filteredMembers, page, true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updatePagination(total, page) {
    const totalPages = Math.ceil(total / membersPerPage);
    const paginationContainer = document.querySelector('.pagination-container');
    
    if (!paginationContainer) return;
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    const queryParams = new URLSearchParams(window.location.search);
    const groupEmail = queryParams.get('group_email') || '{{ $groupInfo['email'] }}';
    
    // Update pagination links
    const prevLink = paginationContainer.querySelector('a[href*="page"]');
    const nextLink = paginationContainer.querySelectorAll('a[href*="page"]')[1];
    const pageLinks = paginationContainer.querySelectorAll('nav a, nav span');
    
    // Update page numbers - we'll do this by updating the existing pagination
    // For now, just update the count display
    const countText = paginationContainer.querySelector('.text-sm.text-gray-700');
    if (countText) {
        const start = (page - 1) * membersPerPage + 1;
        const end = Math.min(page * membersPerPage, total);
        countText.innerHTML = 'Showing <span class="font-medium">' + start + '</span> to <span class="font-medium">' + end + '</span> of <span class="font-medium">' + total.toLocaleString() + '</span> results';
    }
}

function filterMembers(searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') {
        filteredMembers = allMembersData;
    } else {
        const term = searchTerm.toLowerCase().trim();
        filteredMembers = allMembersData.filter(function(member) {
            const name = (member.name || '').toLowerCase();
            const email = (member.email || '').toLowerCase();
            const title = (member.title || '').toLowerCase();
            const department = (member.department || '').toLowerCase();
            const source = (member.source_group || '').toLowerCase();
            
            return name.includes(term) || 
                   email.includes(term) || 
                   title.includes(term) || 
                   department.includes(term) || 
                   source.includes(term);
        });
    }
    
    // Reset to page 1 when filtering
    currentPage = 1;
    renderMembers(filteredMembers, 1, true);
    
    // Update count
    const filteredCount = document.getElementById('filtered-count');
    if (filteredCount) {
        filteredCount.textContent = filteredMembers.length.toLocaleString();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('member-search');
    const filteredCount = document.getElementById('filtered-count');
    
    // Initialize with all members
    filteredMembers = allMembersData;
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchTerm = e.target.value;
            const isSearching = searchTerm.trim() !== '';
            
            if (isSearching) {
                // Client-side search and pagination
                filterMembers(searchTerm);
            } else {
                // Reset to server-side pagination - reload page to show server pagination
                window.location.href = window.location.pathname + '?group_email={{ urlencode($groupInfo['email']) }}&page=1';
            }
        });
    }
});
</script>
@endsection

