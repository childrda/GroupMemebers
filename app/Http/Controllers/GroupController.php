<?php

namespace App\Http\Controllers;

use App\Services\GoogleDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    protected $directoryService;

    public function __construct(GoogleDirectoryService $directoryService)
    {
        $this->directoryService = $directoryService;
    }

    public function index()
    {
        return view('groups.index');
    }

    public function search(Request $request)
    {
        try {
            // Get group email from either POST (form submission) or GET (pagination)
            $groupEmail = trim($request->input('group_email', $request->query('group_email', '')));
            
            if (empty($groupEmail)) {
                return redirect()->route('groups.index')
                    ->with('error', 'Please enter a group name or email address.');
            }
            
            // If user didn't include @, append the domain from config
            if (strpos($groupEmail, '@') === false) {
                $emailDomain = trim(config('services.email_domain') ?? '');
                if (empty($emailDomain)) {
                    return redirect()->route('groups.index')
                        ->with('error', 'Please include the full email address (e.g., group@domain.com) or set EMAIL_DOMAIN in your .env file.');
                }
                $groupEmail = $groupEmail . '@' . $emailDomain;
            }
            
            // Validate the final email format
            if (!filter_var($groupEmail, FILTER_VALIDATE_EMAIL)) {
                $emailDomain = config('services.email_domain');
                $hint = '';
                if ($emailDomain) {
                    $hint = " If you entered just the group name, make sure it doesn't contain invalid characters. Otherwise, please include the full email address (e.g., group@{$emailDomain}).";
                }
                return redirect()->route('groups.index')
                    ->with('error', 'Invalid email format. Please enter a valid group email address.' . $hint);
            }
            
            // Check if we have cached results in session for this group
            $sessionKey = 'group_results_' . md5($groupEmail);
            $cachedData = $request->session()->get($sessionKey);
            
            // Only fetch from API if this is a new search (POST) or cache doesn't exist
            if ($request->isMethod('post') || !$cachedData) {
                // Increase execution time limit for large groups
                set_time_limit(300); // 5 minutes
                
                // Get group info
                $groupInfo = $this->directoryService->getGroupInfo($groupEmail);
                
                // Get all members recursively
                $members = $this->directoryService->getGroupMembersRecursive($groupEmail);
                
                // Remove duplicates
                $uniqueMembers = $this->directoryService->removeDuplicates($members);
                
                // Sort by name
                usort($uniqueMembers, function($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                
                // Store in session for pagination and search
                $cachedData = [
                    'groupInfo' => $groupInfo,
                    'members' => $uniqueMembers,
                    'memberCount' => count($uniqueMembers),
                ];
                $request->session()->put($sessionKey, $cachedData);
            }
            
            // Get data from cache
            $groupInfo = $cachedData['groupInfo'];
            $uniqueMembers = $cachedData['members'];
            $memberCount = $cachedData['memberCount'];
            
            // Paginate results
            $perPage = 50; // Members per page
            $currentPage = (int) $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $paginatedMembers = array_slice($uniqueMembers, $offset, $perPage);
            
            // Build query parameters for pagination (use the final processed group email)
            $queryParams = $request->query();
            $queryParams['group_email'] = $groupEmail;
            
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedMembers,
                $memberCount,
                $perPage,
                $currentPage,
                [
                    'path' => route('groups.search'),
                    'query' => $queryParams,
                ]
            );
            
            // Set the page name for pagination
            $paginator->setPageName('page');
            
            return view('groups.results', [
                'groupInfo' => $groupInfo,
                'members' => $paginatedMembers,
                'allMembers' => $uniqueMembers, // For CSV download and client-side search
                'memberCount' => $memberCount,
                'paginator' => $paginator,
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching group: ' . $e->getMessage());
            
            // Provide more helpful error messages for common issues
            $errorMessage = $e->getMessage();
            $groupEmail = $request->input('group_email');
            
            // Check for timeout error
            if (strpos($errorMessage, 'Maximum execution time') !== false || 
                strpos($errorMessage, 'timeout') !== false ||
                strpos($errorMessage, 'exceeded') !== false) {
                $errorMessage = "The request took too long to complete. This group may be very large or have many nested groups. Please try again or contact support if the issue persists.";
            }
            // Check for group not found error (check error code first, then message)
            elseif (method_exists($e, 'getCode') && $e->getCode() == 404) {
                $errorMessage = "The email address '{$groupEmail}' was not found. Please verify the group email address and try again.";
            } elseif (strpos($errorMessage, 'Resource Not Found') !== false || 
                strpos($errorMessage, 'not found') !== false || 
                strpos($errorMessage, '404') !== false ||
                strpos($errorMessage, 'Invalid Input') !== false ||
                preg_match('/\b404\b/i', $errorMessage)) {
                $errorMessage = "The email address '{$groupEmail}' was not found. Please verify the group email address and try again.";
            }
            // Check for Admin SDK API not enabled error
            elseif (strpos($errorMessage, 'Admin SDK API has not been used') !== false || strpos($errorMessage, 'SERVICE_DISABLED') !== false || strpos($errorMessage, 'accessNotConfigured') !== false) {
                $errorMessage = "Admin SDK API is not enabled. Please:\n\n";
                $errorMessage .= "1. Go to Google Cloud Console → APIs & Services → Library\n";
                $errorMessage .= "2. Search for 'Admin SDK API'\n";
                $errorMessage .= "3. Click on it and press 'Enable'\n";
                $errorMessage .= "4. Wait a few minutes for the API to propagate\n";
                $errorMessage .= "5. Try again";
            }
            // Check for invalid_grant error (domain-wide delegation issue)
            elseif (strpos($errorMessage, 'invalid_grant') !== false || strpos($errorMessage, 'Invalid email or User ID') !== false) {
                $adminEmail = config('services.google.admin_email');
                $errorMessage = "Authentication error: Invalid email or User ID. Please verify:\n\n";
                $errorMessage .= "1. GOOGLE_ADMIN_EMAIL in .env is set to a valid admin email: " . ($adminEmail ?: 'NOT SET') . "\n";
                $errorMessage .= "2. The email exists in your Google Workspace domain\n";
                $errorMessage .= "3. Domain-wide delegation is enabled for your service account\n";
                $errorMessage .= "4. The required scopes are authorized in Google Admin Console";
            }
            
            return redirect()->route('groups.index')
                ->with('error', $errorMessage);
        }
    }

    public function downloadCsv(Request $request)
    {
        $request->validate([
            'group_email' => 'required|string',
        ]);

        // Increase execution time limit for large groups
        set_time_limit(300); // 5 minutes

        try {
            $groupEmail = trim($request->input('group_email'));
            
            if (empty($groupEmail)) {
                return redirect()->route('groups.index')
                    ->with('error', 'Please enter a group name or email address.');
            }
            
            // If user didn't include @, append the domain from config
            if (strpos($groupEmail, '@') === false) {
                $emailDomain = trim(config('services.email_domain') ?? '');
                if (empty($emailDomain)) {
                    return redirect()->route('groups.index')
                        ->with('error', 'Please include the full email address (e.g., group@domain.com) or set EMAIL_DOMAIN in your .env file.');
                }
                $groupEmail = $groupEmail . '@' . $emailDomain;
            }
            
            // Validate the final email format
            if (!filter_var($groupEmail, FILTER_VALIDATE_EMAIL)) {
                $emailDomain = config('services.email_domain');
                $hint = '';
                if ($emailDomain) {
                    $hint = " If you entered just the group name, make sure it doesn't contain invalid characters. Otherwise, please include the full email address (e.g., group@{$emailDomain}).";
                }
                return redirect()->route('groups.index')
                    ->with('error', 'Invalid email format. Please enter a valid group email address.' . $hint);
            }
            
            // Get all members recursively
            $members = $this->directoryService->getGroupMembersRecursive($groupEmail);
            
            // Remove duplicates
            $uniqueMembers = $this->directoryService->removeDuplicates($members);
            
            // Sort by name
            usort($uniqueMembers, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
            
            $filename = 'group-members-' . str_replace('@', '-at-', $groupEmail) . '-' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
            
            $callback = function() use ($uniqueMembers) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for Excel compatibility
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Headers
                fputcsv($file, ['Name', 'Email', 'Source Group', 'Title', 'Department']);
                
                // Data
                foreach ($uniqueMembers as $member) {
                    fputcsv($file, [
                        $member['name'],
                        $member['email'],
                        $member['source_group'],
                        $member['title'] ?? '',
                        $member['department'] ?? '',
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error downloading CSV: ' . $e->getMessage());
            
            // Provide more helpful error messages for common issues
            $errorMessage = $e->getMessage();
            
            // Check for Admin SDK API not enabled error
            if (strpos($errorMessage, 'Admin SDK API has not been used') !== false || strpos($errorMessage, 'SERVICE_DISABLED') !== false || strpos($errorMessage, 'accessNotConfigured') !== false) {
                $errorMessage = "Admin SDK API is not enabled. Please:\n\n";
                $errorMessage .= "1. Go to Google Cloud Console → APIs & Services → Library\n";
                $errorMessage .= "2. Search for 'Admin SDK API'\n";
                $errorMessage .= "3. Click on it and press 'Enable'\n";
                $errorMessage .= "4. Wait a few minutes for the API to propagate\n";
                $errorMessage .= "5. Try again";
            }
            // Check for invalid_grant error (domain-wide delegation issue)
            elseif (strpos($errorMessage, 'invalid_grant') !== false || strpos($errorMessage, 'Invalid email or User ID') !== false) {
                $adminEmail = config('services.google.admin_email');
                $errorMessage = "Authentication error: Invalid email or User ID. Please verify:\n\n";
                $errorMessage .= "1. GOOGLE_ADMIN_EMAIL in .env is set to a valid admin email: " . ($adminEmail ?: 'NOT SET') . "\n";
                $errorMessage .= "2. The email exists in your Google Workspace domain\n";
                $errorMessage .= "3. Domain-wide delegation is enabled for your service account\n";
                $errorMessage .= "4. The required scopes are authorized in Google Admin Console";
            }
            
            return redirect()->route('groups.index')
                ->with('error', $errorMessage);
        }
    }
}

