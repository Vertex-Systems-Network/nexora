<?php

declare(strict_types=1);

namespace App\Nexora\Security\Password;

final class PasswordStrengthEvaluator
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    /**
     * The installer intentionally separates a hard safety floor from the
     * recommended Super Admin password pattern. A password above the floor may
     * be accepted with explicit operator consent when it is not Strong.
     *
     * @return array{
     *   level:string,
     *   score:int,
     *   requirements:array<string,bool>,
     *   minimum_accepted:bool,
     *   character_classes:int,
     *   predictable:bool,
     *   consent_required:bool
     * }
     */
    public function evaluate(string $password): array
    {
        $requirements = [
            'length' => strlen($password) >= 12,
            'lowercase' => preg_match('/[a-z]/', $password) === 1,
            'uppercase' => preg_match('/[A-Z]/', $password) === 1,
            'number' => preg_match('/\d/', $password) === 1,
            'symbol' => preg_match('/[^A-Za-z0-9]/', $password) === 1,
        ];

        $characterClasses = $this->characterClassCount($requirements);
        $minimumAccepted = strlen($password) >= 10 && $characterClasses >= 3;
        $predictable = $this->hasPredictablePattern($password);
        $recommendedPatternComplete = ! in_array(false, $requirements, true);

        $score = 0;
        if (strlen($password) >= 12) {
            $score++;
        }
        if (strlen($password) >= 16) {
            $score++;
        }
        if (strlen($password) >= 20) {
            $score++;
        }
        if (count(array_unique(str_split($password))) >= 10) {
            $score++;
        }
        if (! $predictable) {
            $score++;
        }

        $level = $this->level(
            password: $password,
            minimumAccepted: $minimumAccepted,
            recommendedPatternComplete: $recommendedPatternComplete,
            predictable: $predictable,
            score: $score,
        );

        return [
            'level' => $level,
            'score' => $score,
            'requirements' => $requirements,
            'minimum_accepted' => $minimumAccepted,
            'character_classes' => $characterClasses,
            'predictable' => $predictable,
            'consent_required' => $minimumAccepted && $level !== 'strong',
        ];
    }

    /** @param array<string,bool> $requirements */
    private function characterClassCount(array $requirements): int
    {
        return count(array_filter([
            $requirements['lowercase'],
            $requirements['uppercase'],
            $requirements['number'],
            $requirements['symbol'],
        ]));
    }

    private function level(
        string $password,
        bool $minimumAccepted,
        bool $recommendedPatternComplete,
        bool $predictable,
        int $score,
    ): string {
        if ($password === '' || ! $minimumAccepted) {
            return 'blocked';
        }

        if (! $recommendedPatternComplete) {
            return 'weak';
        }

        if (! $predictable && $score >= 4) {
            return 'strong';
        }

        if ($score >= 2) {
            return 'medium';
        }

        return 'low';
    }

    private function hasPredictablePattern(string $password): bool
    {
        $value = strtolower($password);
        foreach (['password', 'admin', 'nexora', 'qwerty', '123456', 'letmein', 'welcome'] as $pattern) {
            if (str_contains($value, $pattern)) {
                return true;
            }
        }

        if (preg_match('/(.)\1{3,}/', $password) === 1) {
            return true;
        }

        return preg_match('/(?:0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef)/i', $password) === 1;
    }
}
