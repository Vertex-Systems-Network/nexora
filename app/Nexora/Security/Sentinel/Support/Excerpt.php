<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

final class Excerpt
{
    public static function around(string $content, int $line, int $radius = 2): string
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $start = max(1, $line - $radius);
        $end = min(count($lines), $line + $radius);
        $output = [];

        for ($number = $start; $number <= $end; $number++) {
            $text = (string) ($lines[$number - 1] ?? '');
            $preview = function_exists('mb_substr') ? mb_substr($text, 0, 240) : substr($text, 0, 240);
            $output[] = sprintf('%s%4d | %s', $number === $line ? '>' : ' ', $number, $preview);
        }

        return implode("\n", $output);
    }
}
