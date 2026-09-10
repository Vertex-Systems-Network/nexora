<?php

declare(strict_types=1);

/**
 * Return the bounded local-development markers found in one production asset.
 *
 * The macOS check deliberately requires a path boundary before /Users/ and a
 * username segment after it. This avoids treating legitimate application route
 * fragments such as Admin/Users/Form.tsx as developer-home paths while still
 * rejecting /Users/alice/project and file:///Users/alice/project.
 *
 * @return list<string>
 */
function nexoraPerformanceBuildLocalLeaks(string $source): array
{
    $leaks = [];

    foreach (['localhost:5173', '127.0.0.1:5173', 'D:\\laragon\\'] as $literal) {
        if (str_contains($source, $literal)) {
            $leaks[] = $literal;
        }
    }

    if (preg_match('~(?<![A-Za-z0-9_.-])/Users/[^/\\\\\s]+(?:/|\\\\)~', $source) === 1) {
        $leaks[] = '/Users/<user>/';
    }

    return $leaks;
}
