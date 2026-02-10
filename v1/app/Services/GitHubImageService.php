<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GitHubImageService
{
    private $token;
    private $repo;
    private $owner;
    private $baseUrl;

    public function __construct()
    {
        $this->token = env('GITHUB_TOKEN');
        $this->repo = env('GITHUB_REPO', 'grabbaskets-images');
        $this->owner = env('GITHUB_OWNER', 'grabbaskets-hash');
        $this->baseUrl = "https://api.github.com/repos/{$this->owner}/{$this->repo}/contents";
    }

    /**
     * Upload image to GitHub repository
     */
    public function uploadImage($imageFile, $filename = null)
    {
        try {
            if (!$this->token || !$this->repo || !$this->owner || $this->token === 'ghp_test_token_will_be_replaced_with_real_one') {
                throw new Exception('GitHub configuration missing or using test token');
            }

            // Generate filename if not provided
            if (!$filename) {
                $filename = 'product_' . time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
            }

            // Read image content and encode to base64
            $imageContent = base64_encode(file_get_contents($imageFile->getPathname()));
            
            // GitHub API path
            $path = "products/{$filename}";
            $url = "{$this->baseUrl}/{$path}";

            // Prepare GitHub API request
            $response = Http::withHeaders([
                'Authorization' => "token {$this->token}",
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'GrabBaskets-App'
            ])->put($url, [
                'message' => "Add product image: {$filename}",
                'content' => $imageContent,
                'branch' => 'main'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Return the raw GitHub URL for the image
                $rawUrl = "https://raw.githubusercontent.com/{$this->owner}/{$this->repo}/main/products/{$filename}";
                
                Log::info('Image uploaded to GitHub successfully', [
                    'filename' => $filename,
                    'path' => $path,
                    'raw_url' => $rawUrl,
                    'size' => $imageFile->getSize()
                ]);

                return [
                    'success' => true,
                    'url' => $rawUrl,
                    'path' => $path,
                    'filename' => $filename
                ];
            } else {
                Log::error('GitHub upload failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'GitHub upload failed: ' . $response->body()
                ];
            }

        } catch (Exception $e) {
            Log::error('GitHub image upload exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete image from GitHub repository
     */
    public function deleteImage($path)
    {
        try {
            if (!$this->token || !$this->repo || !$this->owner) {
                return false;
            }

            $url = "{$this->baseUrl}/{$path}";

            // First, get the file to obtain its SHA
            $getResponse = Http::withHeaders([
                'Authorization' => "token {$this->token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get($url);

            if (!$getResponse->successful()) {
                return false;
            }

            $fileData = $getResponse->json();
            $sha = $fileData['sha'];

            // Delete the file
            $deleteResponse = Http::withHeaders([
                'Authorization' => "token {$this->token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->delete($url, [
                'message' => "Delete product image: {$path}",
                'sha' => $sha,
                'branch' => 'main'
            ]);

            return $deleteResponse->successful();

        } catch (Exception $e) {
            Log::error('GitHub image deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the public URL for a GitHub-hosted image
     */
    public function getImageUrl($path)
    {
        if (str_starts_with($path, 'https://')) {
            return $path; // Already a full URL
        }
        
        return "https://raw.githubusercontent.com/{$this->owner}/{$this->repo}/main/{$path}";
    }
}