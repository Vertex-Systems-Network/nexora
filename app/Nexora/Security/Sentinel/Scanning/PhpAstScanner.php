<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

final class PhpAstScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $statements = $parser->parse($content);
        } catch (Error $error) {
            $line = max(1, $error->getStartLine());

            return [new SecurityFinding(
                'NEX-AST-0001',
                FindingSeverity::Critical,
                'php-ast',
                'PHP source could not be parsed safely',
                'Sentinel requires a complete syntax tree before package activation. Parse error: '.$error->getMessage(),
                $path,
                $line,
                $line,
                Excerpt::around($content, $line),
                true,
            )];
        }

        if ($statements === null) {
            return [];
        }

        $visitor = new class($path, $content) extends NodeVisitorAbstract
        {
            /** @var list<SecurityFinding> */
            public array $findings = [];

            public function __construct(private readonly string $path, private readonly string $content)
            {
            }

            public function enterNode(Node $node): ?Node
            {
                if ($node instanceof Expr\Eval_) {
                    $this->add('NEX-PHP-0000', FindingSeverity::Critical, 'execution', 'Dynamic PHP execution detected', 'eval() executes runtime-generated PHP and is blocked in Nexora packages.', $node, true);
                }

                if ($node instanceof Expr\ShellExec) {
                    $this->add('NEX-PHP-0008', FindingSeverity::Critical, 'execution', 'Backtick shell execution detected', 'PHP backtick syntax executes an operating-system shell command and is blocked.', $node, true);
                }

                if ($node instanceof Expr\Include_) {
                    $this->add('NEX-PHP-0050', FindingSeverity::Medium, 'execution', 'Direct include/require detected', 'Package code should use controlled autoloading. Dynamic include/require expressions can cross package boundaries.', $node, false);
                }

                if ($node instanceof Expr\FuncCall) {
                    if ($node->name instanceof Node\Name) {
                        $name = strtolower($node->name->toString());
                        $rules = [
                            'exec' => ['NEX-PHP-0001', FindingSeverity::Critical, 'OS command execution', true],
                            'shell_exec' => ['NEX-PHP-0002', FindingSeverity::Critical, 'Shell command execution', true],
                            'system' => ['NEX-PHP-0003', FindingSeverity::Critical, 'OS command execution', true],
                            'passthru' => ['NEX-PHP-0004', FindingSeverity::Critical, 'Direct process output execution', true],
                            'proc_open' => ['NEX-PHP-0005', FindingSeverity::Critical, 'Process spawning', true],
                            'popen' => ['NEX-PHP-0006', FindingSeverity::Critical, 'Process spawning', true],
                            'pcntl_exec' => ['NEX-PHP-0007', FindingSeverity::Critical, 'Process replacement', true],
                            'unserialize' => ['NEX-PHP-0010', FindingSeverity::High, 'Unsafe deserialization primitive', false],
                            'curl_exec' => ['NEX-PHP-0020', FindingSeverity::High, 'Direct outbound HTTP', false],
                            'fsockopen' => ['NEX-PHP-0021', FindingSeverity::High, 'Direct socket access', false],
                            'pfsockopen' => ['NEX-PHP-0022', FindingSeverity::High, 'Persistent raw socket access', false],
                            'stream_socket_client' => ['NEX-PHP-0023', FindingSeverity::High, 'Direct socket client', false],
                            'chmod' => ['NEX-PHP-0031', FindingSeverity::High, 'Filesystem permission mutation', false],
                            'chown' => ['NEX-PHP-0032', FindingSeverity::High, 'Filesystem ownership mutation', false],
                        ];
                        if (isset($rules[$name])) {
                            [$rule, $severity, $title, $hard] = $rules[$name];
                            $this->add($rule, $severity, 'php-ast', $title, "{$name}() was identified in the parsed PHP syntax tree and requires the corresponding Nexora security policy.", $node, $hard);
                        }
                    } else {
                        $this->add('NEX-AST-0002', FindingSeverity::High, 'obfuscation', 'Dynamic function invocation detected', 'The called function name is computed at runtime, which can conceal dangerous execution and bypass simple allowlists.', $node, false);
                    }
                }

                if ($node instanceof Expr\Variable && ! is_string($node->name)) {
                    $this->add('NEX-AST-0003', FindingSeverity::Medium, 'obfuscation', 'Variable-variable expression detected', 'Computed variable names make data/control flow harder to verify and require review in third-party packages.', $node, false);
                }

                if ($node instanceof Expr\ErrorSuppress) {
                    $this->add('NEX-AST-0004', FindingSeverity::Low, 'obfuscation', 'Error suppression operator detected', 'The @ operator can hide security-relevant failures and should be avoided in package code.', $node, false);
                }

                return null;
            }

            private function add(string $rule, FindingSeverity $severity, string $category, string $title, string $message, Node $node, bool $hard): void
            {
                $line = max(1, $node->getStartLine());
                $end = max($line, $node->getEndLine());
                $this->findings[] = new SecurityFinding(
                    $rule,
                    $severity,
                    $category,
                    $title,
                    $message,
                    $this->path,
                    $line,
                    $end,
                    Excerpt::around($this->content, $line),
                    $hard,
                );
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return $visitor->findings;
    }
}
