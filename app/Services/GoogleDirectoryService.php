<?php

namespace App\Services;

use Google_Client;
use Google_Service_Directory;
use Illuminate\Support\Facades\Log;

class GoogleDirectoryService
{
    private $client;
    private $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        
        // Set up service account credentials
        $credentialsPath = storage_path('app/google-credentials.json');
        
        if (!file_exists($credentialsPath)) {
            throw new \Exception('Google service account credentials file not found at: ' . $credentialsPath);
        }
        
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope([
            'https://www.googleapis.com/auth/admin.directory.group.readonly',
            'https://www.googleapis.com/auth/admin.directory.group.member.readonly',
            'https://www.googleapis.com/auth/admin.directory.user.readonly',
        ]);
        
        // Set HTTP client options for timeout handling
        $this->client->setHttpClient(new \GuzzleHttp\Client([
            'timeout' => 60, // 60 seconds per request
            'connect_timeout' => 10,
        ]));
        
        // Set subject (admin user email) for domain-wide delegation
        $subject = config('services.google.admin_email');
        if (empty($subject)) {
            throw new \Exception('GOOGLE_ADMIN_EMAIL is not set in .env file. Please set it to a valid Google Workspace admin email address.');
        }
        
        // Validate email format
        if (!filter_var($subject, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('GOOGLE_ADMIN_EMAIL is not a valid email address: ' . $subject);
        }
        
        $this->client->setSubject($subject);
        
        $this->service = new Google_Service_Directory($this->client);
    }

    /**
     * Get all members of a group recursively
     */
    public function getGroupMembersRecursive($groupEmail, $visitedGroups = [], $sourceGroup = null)
    {
        // Prevent infinite loops
        if (in_array($groupEmail, $visitedGroups)) {
            return [];
        }
        
        $visitedGroups[] = $groupEmail;
        $members = [];
        
        try {
            // Get group members
            $membersResponse = $this->service->members->listMembers($groupEmail);
            
            foreach ($membersResponse->getMembers() as $member) {
                $memberType = $member->getType();
                $memberEmail = $member->getEmail();
                
                if ($memberType === 'USER') {
                    // Get user details
                    try {
                        $user = $this->service->users->get($memberEmail);
                        
                        // Safely extract organization data
                        $title = null;
                        $department = null;
                        $organizations = $user->getOrganizations();
                        if ($organizations && is_array($organizations) && count($organizations) > 0) {
                            $org = $organizations[0];
                            // Handle both object and array formats
                            if (is_object($org)) {
                                $title = $org->getTitle() ?? null;
                                $department = $org->getDepartment() ?? null;
                            } elseif (is_array($org)) {
                                $title = $org['title'] ?? null;
                                $department = $org['department'] ?? null;
                            }
                        }
                        
                        $members[] = [
                            'email' => $user->getPrimaryEmail(),
                            'name' => $user->getName()->getFullName(),
                            'title' => $title,
                            'department' => $department,
                            'source_group' => $sourceGroup ?? $groupEmail,
                        ];
                    } catch (\Exception $e) {
                        Log::warning("Could not fetch user details for {$memberEmail}: " . $e->getMessage());
                        // Still add the member with basic info
                        // Extract name from email (first part before @) as fallback
                        $nameFromEmail = explode('@', $memberEmail)[0];
                        $nameFromEmail = str_replace(['.', '_'], ' ', $nameFromEmail);
                        $nameFromEmail = ucwords($nameFromEmail);
                        
                        $members[] = [
                            'email' => $memberEmail,
                            'name' => $nameFromEmail ?: 'Unknown',
                            'title' => null,
                            'department' => null,
                            'source_group' => $sourceGroup ?? $groupEmail,
                        ];
                    }
                } elseif ($memberType === 'GROUP') {
                    // Recursively get members of nested group
                    $nestedMembers = $this->getGroupMembersRecursive(
                        $memberEmail,
                        $visitedGroups,
                        $sourceGroup ?? $groupEmail
                    );
                    $members = array_merge($members, $nestedMembers);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching group members for {$groupEmail}: " . $e->getMessage());
            throw $e;
        }
        
        return $members;
    }

    /**
     * Get group information
     */
    public function getGroupInfo($groupEmail)
    {
        try {
            $group = $this->service->groups->get($groupEmail);
            return [
                'email' => $group->getEmail(),
                'name' => $group->getName(),
                'description' => $group->getDescription(),
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching group info for {$groupEmail}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove duplicate users based on email
     */
    public function removeDuplicates($members)
    {
        $uniqueMembers = [];
        $seenEmails = [];
        
        foreach ($members as $member) {
            $email = strtolower($member['email']);
            if (!in_array($email, $seenEmails)) {
                $seenEmails[] = $email;
                $uniqueMembers[] = $member;
            } else {
                // If duplicate, keep the one with more information
                $existingIndex = array_search($email, array_map('strtolower', array_column($uniqueMembers, 'email')));
                if ($existingIndex !== false) {
                    $existing = $uniqueMembers[$existingIndex];
                    // Merge source groups if different
                    if ($existing['source_group'] !== $member['source_group']) {
                        $uniqueMembers[$existingIndex]['source_group'] = $existing['source_group'] . ', ' . $member['source_group'];
                    }
                    // Update if new member has more info
                    if (empty($existing['title']) && !empty($member['title'])) {
                        $uniqueMembers[$existingIndex]['title'] = $member['title'];
                    }
                    if (empty($existing['department']) && !empty($member['department'])) {
                        $uniqueMembers[$existingIndex]['department'] = $member['department'];
                    }
                }
            }
        }
        
        return $uniqueMembers;
    }
}

