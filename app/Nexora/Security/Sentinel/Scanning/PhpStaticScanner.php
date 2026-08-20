<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class PhpStaticScanner
{
    /** @var array<string, array{rule:string,severity:FindingSeverity,title:string,message:string,hard:bool}> */
    private array $functionRules;

    public function __construct()
    {
        $this->functionRules = [
            'exec' => $this->rule('NEX-PHP-0001', FindingSeverity::Critical, 'OS command execution', 'exec() can execute operating-system commands outside Nexora capability controls.', true),
            'shell_exec' => $this->rule('NEX-PHP-0002', FindingSeverity::Critical, 'Shell command execution', 'shell_exec() bypasses Nexora process isolation and capability controls.', true),
            'system' => $this->rule('NEX-PHP-0003', FindingSeverity::Critical, 'OS command execution', 'system() can execute arbitrary operating-system commands.', true),
            'passthru' => $this->rule('NEX-PHP-0004', FindingSeverity::Critical, 'Direct process output execution', 'passthru() invokes an external program directly.', true),
            'proc_open' => $this->rule('NEX-PHP-0005', FindingSeverity::Critical, 'Process spawning', 'proc_open() can spawn and control external processes.', true),
            'popen' => $this->rule('NEX-PHP-0006', FindingSeverity::Critical, 'Process spawning', 'popen() opens an external process pipe.', true),
            'pcntl_exec' => $this->rule('NEX-PHP-0007', FindingSeverity::Critical, 'Process replacement', 'pcntl_exec() replaces the current process with an external program.', true),
            'unserialize' => $this->rule('NEX-PHP-0010', FindingSeverity::High, 'Unsafe deserialization primitive', 'unserialize() may enable object injection when fed untrusted data. Use constrained serializers and typed DTOs.', false),
            'base64_decode' => $this->rule('NEX-PHP-0011', FindingSeverity::Medium, 'Encoded payload decoding', 'base64_decode() is commonly legitimate but is also used to hide executable payloads. Review the decoded data flow.', false),
            'gzinflate' => $this->rule('NEX-PHP-0012', FindingSeverity::Medium, 'Compressed payload expansion', 'gzinflate() can be used to unpack obfuscated executable code.', false),
            'gzuncompress' => $this->rule('NEX-PHP-0013', FindingSeverity::Medium, 'Compressed payload expansion', 'gzuncompress() can hide payloads from basic source review.', false),
            'str_rot13' => $this->rule('NEX-PHP-0014', FindingSeverity::Low, 'String obfuscation primitive', 'str_rot13() is unusual in application packages and should be reviewed when combined with dynamic execution.', false),
            'curl_exec' => $this->rule('NEX-PHP-0020', FindingSeverity::High, 'Direct outbound HTTP', 'Direct cURL execution bypasses the Nexora network broker and destination policy layer.', false),
            'fsockopen' => $this->rule('NEX-PHP-0021', FindingSeverity::High, 'Direct socket access', 'fsockopen() opens raw network sockets outside the Nexora network broker.', false),
            'pfsockopen' => $this->rule('NEX-PHP-0022', FindingSeverity::High, 'Persistent raw socket access', 'pfsockopen() opens persistent sockets outside platform network policy.', false),
            'stream_socket_client' => $this->rule('NEX-PHP-0023', FindingSeverity::High, 'Direct socket client', 'stream_socket_client() bypasses the platform network broker.', false),
            'unlink' => $this->rule('NEX-PHP-0030', FindingSeverity::Medium, 'Direct filesystem mutation', 'unlink() deletes files directly instead of using scoped Nexora storage APIs.', false),
            'chmod' => $this->rule('NEX-PHP-0031', FindingSeverity::High, 'Filesystem permission mutation', 'chmod() modifies filesystem permissions and requires explicit privileged review.', false),
            'chown' => $this->rule('NEX-PHP-0032', FindingSeverity::High, 'Filesystem ownership mutation', 'chown() changes file ownership and is not allowed in restricted extension code.', false),
            'getenv' => $this->rule('NEX-PHP-0040', FindingSeverity::Medium, 'Direct environment access', 'Direct environment reads may expose platform secrets. Extensions should use declared Nexora secret capabilities.', false),
        ];
    }

    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $findings = [];
        $tokens = token_get_all($content);
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];
            if (! is_array($token)) {
                continue;
            }

            [$id, $text, $line] = $token;
            if ($id === T_EVAL) {
                $findings[] = $this->finding('NEX-PHP-0000', FindingSeverity::Critical, 'execution', 'Dynamic PHP execution detected', 'eval() executes runtime-generated PHP and is blocked in Nexora packages.', $path, $line, $content, true);
                continue;
            }

            if (in_array($id, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
                $findings[] = $this->finding('NEX-PHP-0050', FindingSeverity::Medium, 'execution', 'Direct include/require detected', 'Package code should rely on controlled autoloading. Dynamic includes can bypass package boundaries and deserve review.', $path, $line, $content, false);
                continue;
            }

            if ($id !== T_STRING) {
                continue;
            }

            $name = strtolower($text);
            if (! isset($this->functionRules[$name]) || ! $this->looksLikeFunctionCall($tokens, $index)) {
                continue;
            }

            $rule = $this->functionRules[$name];
            $findings[] = $this->finding($rule['rule'], $rule['severity'], 'php', $rule['title'], $rule['message'], $path, $line, $content, $rule['hard']);
        }

        $this->scanTextHeuristics($path, $content, $findings);

        return $this->deduplicate($findings);
    }

    /** @param list<SecurityFinding> $findings */
    private function scanTextHeuristics(string $path, string $content, array &$findings): void
    {
        $patterns = [
            ['NEX-PHP-0060', FindingSeverity::Critical, '/(?:php|data|expect):\/\//i', 'Dangerous stream-wrapper URI', 'Executable or inline-data stream wrappers can be used to load hidden payloads.', true],
            ['NEX-PHP-0061', FindingSeverity::High, '/\bFFI\s*::|new\s+FFI\b/i', 'PHP FFI usage detected', 'FFI can call native libraries directly and is incompatible with restricted package execution.', true],
            ['NEX-PHP-0062', FindingSeverity::High, '/\bClosure\s*::\s*bind\s*\(/i', 'Closure scope manipulation detected', 'Closure::bind() can bypass normal object encapsulation and requires privileged review.', false],
            ['NEX-PHP-0063', FindingSeverity::High, '/\bDB\s*::\s*(?:statement|unprepared)\s*\(/i', 'Raw database statement detected', 'Raw SQL execution bypasses future extension database scoping and schema policy.', false],
            ['NEX-PHP-0064', FindingSeverity::Medium, '/\benv\s*\(/i', 'Runtime env() access detected', 'Extensions should receive secrets/configuration through typed Nexora contracts rather than reading the process environment directly.', false],
        ];

        foreach ($patterns as [$rule, $severity, $pattern, $title, $message, $hard]) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $offset = (int) $match[1];
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                $findings[] = $this->finding($rule, $severity, 'php', $title, $message, $path, $line, $content, $hard);
            }
        }

        if (preg_match_all('/[A-Za-z0-9+\/]{800,}={0,2}/', $content, $encoded, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($encoded[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = $this->finding('NEX-PHP-0065', FindingSeverity::High, 'obfuscation', 'Large encoded payload detected', 'A very large base64-like string can hide executable code or embedded binaries and requires review.', $path, $line, $content, false);
            }
        }

        if (preg_match('/base64_decode\s*\(/i', $content) === 1 && preg_match('/\beval\s*\(/i', $content) === 1) {
            $line = max(1, substr_count(substr($content, 0, (int) (stripos($content, 'base64_decode') ?: 0)), "\n") + 1);
            $findings[] = $this->finding('NEX-PHP-0066', FindingSeverity::Critical, 'obfuscation', 'Encoded data is combined with dynamic execution', 'The package combines payload decoding with eval(), a strong backdoor/loader indicator.', $path, $line, $content, true);
        }
    }

    /** @param list<mixed> $tokens */
    private function looksLikeFunctionCall(array $tokens, int $index): bool
    {
        for ($next = $index + 1, $count = count($tokens); $next < $count; $next++) {
            $token = $tokens[$next];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token === '(';
        }

        return false;
    }

    /** @return array{rule:string,severity:FindingSeverity,title:string,message:string,hard:bool} */
    private function rule(string $rule, FindingSeverity $severity, string $title, string $message, bool $hard): array
    {
        return compact('rule', 'severity', 'title', 'message', 'hard');
    }

    private function finding(string $rule, FindingSeverity $severity, string $category, string $title, string $message, string $path, int $line, string $content, bool $hard): SecurityFinding
    {
        return new SecurityFinding($rule, $severity, $category, $title, $message, $path, $line, $line, Excerpt::around($content, $line), $hard);
    }

    /** @param list<SecurityFinding> $findings @return list<SecurityFinding> */
    private function deduplicate(array $findings): array
    {
        $seen = [];
        $result = [];
        foreach ($findings as $finding) {
            $key = implode('|', [$finding->ruleId, $finding->filePath ?? '', (string) ($finding->lineStart ?? 0)]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $finding;
        }

        return $result;
    }
}
