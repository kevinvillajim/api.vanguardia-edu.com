<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Generate storage URL for course assets based on actual storage structure
     * The files are organized as: courses/curso{id}/filename.ext
     * 
     * @param string $path - Original path from database (e.g., '/c1Banner1.jpg')
     * @param int|null $courseId - Course ID to determine the curso folder
     * @return string
     */
    public static function courseAssetUrl(string $path, ?int $courseId = null): string
    {
        // Remove leading slash if present
        $cleanPath = ltrim($path, '/');
        
        // If it's already a full storage URL path (with /storage/), clean it and use Storage::url
        if (str_starts_with($path, '/storage/')) {
            $relativePath = str_replace('/storage/', '', $path);
            return Storage::url($relativePath);
        }
        
        // If path already includes the storage structure, use as is
        if (str_starts_with($cleanPath, 'courses/') || str_starts_with($cleanPath, 'uploads/')) {
            return Storage::url($cleanPath);
        }
        
        // If it's a full URL, return as is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        // Try to determine course ID from filename if not provided
        if (!$courseId) {
            // Extract course number from filename patterns like c1Banner1.jpg, c2Banner1.jpg, etc.
            if (preg_match('/c(\d+)/', $cleanPath, $matches)) {
                $courseId = (int) $matches[1];
            }
        }
        
        // If we have a course ID, use the curso{id} structure
        if ($courseId) {
            $fullPath = "courses/curso{$courseId}/{$cleanPath}";
            return Storage::url($fullPath);
        }
        
        // Fallback: try to find the file in any curso directory
        for ($i = 1; $i <= 10; $i++) { // Check up to curso10
            $testPath = "courses/curso{$i}/{$cleanPath}";
            if (Storage::disk('public')->exists($testPath)) {
                return Storage::url($testPath);
            }
        }
        
        // Final fallback: return original path with storage prefix
        return Storage::url("courses/{$cleanPath}");
    }
    
    /**
     * Generate URL for course banner
     */
    public static function courseBannerUrl(string $path, ?int $courseId = null): string
    {
        return self::courseAssetUrl($path, $courseId);
    }
    
    /**
     * Generate URL for course image
     */
    public static function courseImageUrl(string $path, ?int $courseId = null): string
    {
        return self::courseAssetUrl($path, $courseId);
    }
    
    /**
     * Generate URL for course video
     */
    public static function courseVideoUrl(string $path, ?int $courseId = null): string
    {
        return self::courseAssetUrl($path, $courseId);
    }
    
    /**
     * Generate URL for course document
     */
    public static function courseDocumentUrl(string $path, ?int $courseId = null): string
    {
        return self::courseAssetUrl($path, $courseId);
    }
}