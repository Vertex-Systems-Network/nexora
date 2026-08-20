<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Document> */
final class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);
        return [
            'uuid' => (string) Str::uuid(),
            'type' => 'document',
            'status' => 'draft',
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(14),
            'content' => ['version' => 1, 'blocks' => []],
            'metadata' => [],
            'schema_version' => 1,
            'author_id' => User::factory(),
            'last_edited_by' => null,
            'published_at' => null,
        ];
    }
}
