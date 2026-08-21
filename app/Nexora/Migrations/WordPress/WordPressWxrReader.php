<?php

declare(strict_types=1);

namespace App\Nexora\Migrations\WordPress;

use Generator;
use RuntimeException;
use XMLReader;

final class WordPressWxrReader
{
    /**
     * @return Generator<int,array<string,mixed>>
     */
    public function items(string $path): Generator
    {
        if (! class_exists(XMLReader::class)) {
            throw new RuntimeException('WordPress WXR import requires the PHP XMLReader extension.');
        }
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The staged WordPress export is unavailable.');
        }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > 52_428_800) {
            throw new RuntimeException('The staged WordPress export must be between 1 byte and 50 MB.');
        }

        $reader = new XMLReader();
        $flags = LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING;
        if (! $reader->open($path, null, $flags)) {
            throw new RuntimeException('Unable to open the WordPress WXR export.');
        }

        try {
            $reader->setParserProperty(XMLReader::LOADDTD, false);
            $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);
            $reader->setParserProperty(XMLReader::VALIDATE, false);

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'item') {
                    continue;
                }

                $item = $this->readItem($reader);
                if ($item !== null) {
                    yield $item;
                }
            }
        } finally {
            $reader->close();
        }
    }

    /** @return array<string,mixed>|null */
    private function readItem(XMLReader $reader): ?array
    {
        $itemDepth = $reader->depth;
        $data = [
            'title' => '',
            'link' => '',
            'guid' => '',
            'creator' => '',
            'post_id' => '',
            'post_date' => '',
            'status' => 'draft',
            'post_name' => '',
            'post_type' => '',
            'content' => '',
            'excerpt' => '',
            'attachment_url' => '',
            'terms' => [],
        ];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $itemDepth && $reader->localName === 'item') {
                break;
            }
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->depth !== $itemDepth + 1) {
                continue;
            }

            $name = $reader->name;
            if ($name === 'category') {
                $value = trim($reader->readString());
                if ($value !== '') {
                    $data['terms'][] = [
                        'domain' => trim((string) $reader->getAttribute('domain')),
                        'slug' => trim((string) $reader->getAttribute('nicename')),
                        'name' => $value,
                    ];
                }
                continue;
            }

            $field = match ($name) {
                'title' => 'title',
                'link' => 'link',
                'guid' => 'guid',
                'dc:creator' => 'creator',
                'wp:post_id' => 'post_id',
                'wp:post_date', 'wp:post_date_gmt' => 'post_date',
                'wp:status' => 'status',
                'wp:post_name' => 'post_name',
                'wp:post_type' => 'post_type',
                'content:encoded' => 'content',
                'excerpt:encoded' => 'excerpt',
                'wp:attachment_url' => 'attachment_url',
                default => null,
            };

            if ($field !== null) {
                $value = $reader->readString();
                if ($field !== 'post_date' || trim((string) $data['post_date']) === '') {
                    $data[$field] = $value;
                }
            }
        }

        $postType = trim((string) $data['post_type']);
        if (! in_array($postType, ['post', 'page'], true)) {
            return null;
        }

        $postId = trim((string) $data['post_id']);
        $guid = trim((string) $data['guid']);
        $sourceKey = $postId !== '' ? 'wordpress:post:'.$postId : 'wordpress:guid:'.hash('sha256', $guid.'|'.(string) $data['title']);
        $data['source_key'] = $sourceKey;

        return $data;
    }
}
