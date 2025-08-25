<?php

namespace App\Domain\Course\DTOs;

class ComponentDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly array $content,
        public readonly int $order,
        public readonly ?int $duration = null,
        public readonly bool $is_mandatory = true,
        public readonly bool $is_active = true,
        public readonly ?array $metadata = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            title: $data['title'],
            content: $data['content'] ?? [],
            order: $data['order'] ?? 0,
            duration: $data['duration'] ?? null,
            is_mandatory: (bool) ($data['is_mandatory'] ?? true),
            is_active: (bool) ($data['is_active'] ?? true),
            metadata: $data['metadata'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'content' => json_encode($this->content),
            'order' => $this->order,
            'duration' => $this->duration,
            'is_mandatory' => $this->is_mandatory,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata ? json_encode($this->metadata) : null,
        ];
    }
}