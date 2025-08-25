<?php

namespace App\Domain\Course\DTOs;

class ModuleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $order_index,
        public readonly bool $is_published = true,
        public readonly array $components = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            order_index: $data['order_index'] ?? 1,
            is_published: (bool) ($data['is_published'] ?? true),
            components: $data['components'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'is_published' => $this->is_published,
        ];
    }
}