<?php

namespace App\Domain\Course\DTOs;

class CreateCourseDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?int $category_id,
        public readonly string $difficulty_level,
        public readonly int $duration_hours,
        public readonly float $price,
        public readonly bool $is_featured,
        public readonly ?string $banner_image,
        public readonly array $modules = []
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            category_id: $data['category_id'] ?? null,
            difficulty_level: $data['difficulty_level'],
            duration_hours: $data['duration_hours'] ?? 0,
            price: (float) ($data['price'] ?? 0),
            is_featured: (bool) ($data['is_featured'] ?? false),
            banner_image: $data['banner_image'] ?? null,
            modules: $data['modules'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'difficulty_level' => $this->difficulty_level,
            'duration_hours' => $this->duration_hours,
            'price' => $this->price,
            'is_featured' => $this->is_featured,
            'banner_image' => $this->banner_image,
            'is_published' => false, // Always start as draft
            'enrollment_count' => 0,
            'rating' => 0.0,
        ];
    }
}