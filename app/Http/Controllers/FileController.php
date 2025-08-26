<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Upload a file for course components
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'type' => 'nullable|string|in:image,video,document,audio,any',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $type = $request->input('type', 'any');
            
            // Validate file type based on component type
            $this->validateFileType($file, $type);
            
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            
            // Determine storage path based on file type
            $storagePath = $this->getStoragePath($type);
            
            // Store file
            $path = $file->storeAs($storagePath, $filename, 'public');
            
            // Return relative path, not full URL (Resources will handle URL generation)
            $url = $path;
            
            // Get file metadata
            $metadata = $this->getFileMetadata($file, $originalName);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => $url,
                    'filename' => $filename,
                    'originalName' => $originalName,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'metadata' => $metadata
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an uploaded file
     */
    public function deleteFile(string $filename): JsonResponse
    {
        try {
            // Search for file in all possible directories
            $possiblePaths = [
                'uploads/images/' . $filename,
                'uploads/videos/' . $filename,
                'uploads/documents/' . $filename,
                'uploads/audio/' . $filename,
                'uploads/files/' . $filename
            ];

            $deleted = false;
            foreach ($possiblePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $deleted = true;
                    break;
                }
            }

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate file type based on component type
     */
    private function validateFileType($file, string $type): void
    {
        $mimeType = $file->getMimeType();
        
        switch ($type) {
            case 'image':
                if (!str_starts_with($mimeType, 'image/')) {
                    throw new \Exception('File must be an image');
                }
                break;
                
            case 'video':
                if (!str_starts_with($mimeType, 'video/')) {
                    throw new \Exception('File must be a video');
                }
                break;
                
            case 'audio':
                if (!str_starts_with($mimeType, 'audio/')) {
                    throw new \Exception('File must be an audio file');
                }
                break;
                
            case 'document':
                $allowedDocTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain',
                    'text/csv'
                ];
                
                if (!in_array($mimeType, $allowedDocTypes)) {
                    throw new \Exception('File must be a supported document type (PDF, Word, Excel, PowerPoint, or Text)');
                }
                break;
        }
    }

    /**
     * Get storage path based on file type
     */
    private function getStoragePath(string $type): string
    {
        switch ($type) {
            case 'image':
                return 'uploads/images';
            case 'video':
                return 'uploads/videos';
            case 'audio':
                return 'uploads/audio';
            case 'document':
                return 'uploads/documents';
            default:
                return 'uploads/files';
        }
    }

    /**
     * Get file metadata
     */
    private function getFileMetadata($file, string $originalName): array
    {
        $metadata = [
            'fileName' => $originalName,
            'fileSize' => $file->getSize(),
            'fileType' => $file->getMimeType()
        ];

        // Add specific metadata for different file types
        $mimeType = $file->getMimeType();
        
        if (str_starts_with($mimeType, 'image/')) {
            // For images, we could add dimensions if needed
            $metadata['category'] = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            // For videos, we could add duration if needed
            $metadata['category'] = 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            // For audio, we could add duration if needed  
            $metadata['category'] = 'audio';
        } else {
            $metadata['category'] = 'document';
        }

        return $metadata;
    }
}