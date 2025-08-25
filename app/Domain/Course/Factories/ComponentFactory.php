<?php

namespace App\Domain\Course\Factories;

use App\Helpers\StorageHelper;

class ComponentFactory
{
    /**
     * Create component based on type
     */
    public function createComponent(string $type, array $content, int $moduleId): array
    {
        return match ($type) {
            'banner' => $this->createBannerComponent($content, $moduleId),
            'video' => $this->createVideoComponent($content, $moduleId),
            'reading' => $this->createReadingComponent($content, $moduleId),
            'image' => $this->createImageComponent($content, $moduleId),
            'document' => $this->createDocumentComponent($content, $moduleId),
            'audio' => $this->createAudioComponent($content, $moduleId),
            'quiz' => $this->createQuizComponent($content, $moduleId),
            'interactive' => $this->createInteractiveComponent($content, $moduleId),
            default => throw new \InvalidArgumentException("Unknown component type: {$type}")
        };
    }

    /**
     * Create banner component
     */
    private function createBannerComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'img' => $this->processImageUrl($content['img'] ?? ''),
            'subtitle' => $content['subtitle'] ?? null,
            'description' => $content['description'] ?? null,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'banner',
                'has_image' => !empty($processedContent['img']),
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create video component
     */
    private function createVideoComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'src' => $this->processVideoUrl($content['src'] ?? ''),
            'poster' => $this->processImageUrl($content['poster'] ?? ''),
            'description' => $content['description'] ?? null,
            'duration' => $content['duration'] ?? null,
            'autoplay' => $content['autoplay'] ?? false,
            'controls' => $content['controls'] ?? true,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'video',
                'duration_seconds' => $this->extractDuration($processedContent['duration']),
                'has_poster' => !empty($processedContent['poster']),
                'file_size' => $content['file_size'] ?? null,
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create reading component
     */
    private function createReadingComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'text' => $content['text'] ?? '',
            'format' => $content['format'] ?? 'html', // html, markdown, plain
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'reading',
                'word_count' => str_word_count(strip_tags($processedContent['text'])),
                'estimated_read_time' => $this->calculateReadingTime($processedContent['text']),
                'format' => $processedContent['format'],
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create image component
     */
    private function createImageComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'img' => $this->processImageUrl($content['img'] ?? ''),
            'alt' => $content['alt'] ?? '',
            'caption' => $content['caption'] ?? null,
            'description' => $content['description'] ?? null,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'image',
                'has_caption' => !empty($processedContent['caption']),
                'alt_text' => $processedContent['alt'],
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create document component
     */
    private function createDocumentComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'file_url' => $this->processDocumentUrl($content['file_url'] ?? ''),
            'file_name' => $content['file_name'] ?? '',
            'file_type' => $content['file_type'] ?? '',
            'description' => $content['description'] ?? null,
            'downloadable' => $content['downloadable'] ?? true,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'document',
                'file_type' => $processedContent['file_type'],
                'file_size' => $content['file_size'] ?? null,
                'is_downloadable' => $processedContent['downloadable'],
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create audio component
     */
    private function createAudioComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'src' => $this->processAudioUrl($content['src'] ?? ''),
            'description' => $content['description'] ?? null,
            'duration' => $content['duration'] ?? null,
            'autoplay' => $content['autoplay'] ?? false,
            'controls' => $content['controls'] ?? true,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'audio',
                'duration_seconds' => $this->extractDuration($processedContent['duration']),
                'file_size' => $content['file_size'] ?? null,
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create quiz component
     */
    private function createQuizComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'questions' => $content['questions'] ?? [],
            'passing_score' => $content['passing_score'] ?? 70,
            'time_limit' => $content['time_limit'] ?? null,
            'attempts_allowed' => $content['attempts_allowed'] ?? 3,
            'show_correct_answers' => $content['show_correct_answers'] ?? true,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'quiz',
                'question_count' => count($processedContent['questions']),
                'has_time_limit' => !is_null($processedContent['time_limit']),
                'max_attempts' => $processedContent['attempts_allowed'],
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Create interactive component
     */
    private function createInteractiveComponent(array $content, int $moduleId): array
    {
        $processedContent = [
            'title' => $content['title'] ?? '',
            'type' => $content['type'] ?? 'generic', // generic, simulation, game, etc.
            'data' => $content['data'] ?? [],
            'instructions' => $content['instructions'] ?? null,
        ];

        return [
            'content' => $processedContent,
            'metadata' => [
                'component_type' => 'interactive',
                'interactive_type' => $processedContent['type'],
                'has_instructions' => !empty($processedContent['instructions']),
                'created_at' => now()->toISOString(),
            ]
        ];
    }

    // ===== HELPER METHODS =====

    private function processImageUrl(string $url): string
    {
        if (empty($url)) return '';
        
        // If it's already a full URL, return as is
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        // Use StorageHelper to process the URL
        return $url; // Will be processed by StorageHelper in the controller
    }

    private function processVideoUrl(string $url): string
    {
        if (empty($url)) return '';
        
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return $url; // Will be processed by StorageHelper in the controller
    }

    private function processDocumentUrl(string $url): string
    {
        if (empty($url)) return '';
        
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return $url; // Will be processed by StorageHelper in the controller
    }

    private function processAudioUrl(string $url): string
    {
        if (empty($url)) return '';
        
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return $url; // Will be processed by StorageHelper in the controller
    }

    private function extractDuration(mixed $duration): ?int
    {
        if (is_numeric($duration)) {
            return (int) $duration;
        }

        if (is_string($duration)) {
            // Try to parse duration strings like "5:30", "1:23:45", etc.
            if (preg_match('/^(\d+):(\d+)(?::(\d+))?$/', $duration, $matches)) {
                $hours = isset($matches[3]) ? (int) $matches[1] : 0;
                $minutes = isset($matches[3]) ? (int) $matches[2] : (int) $matches[1];
                $seconds = isset($matches[3]) ? (int) $matches[3] : (int) $matches[2];
                
                return $hours * 3600 + $minutes * 60 + $seconds;
            }
        }

        return null;
    }

    private function calculateReadingTime(string $text): int
    {
        // Average reading speed: 200 words per minute
        $wordCount = str_word_count(strip_tags($text));
        return max(1, ceil($wordCount / 200)); // Minimum 1 minute
    }
}