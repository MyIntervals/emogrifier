<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallExpressionNode;

/**
 * Ignore PHPStan warnings where the DocBlocks indicate that a conditional expression would always be true (or false),
 * but a programming mistake elsewhere could lead to that not being the case, for the following:
 * - `assert($object instanceof Class);`.
 *
 * @internal
 */
final class IgnoreBooleanAlways implements IgnoreErrorExtension
{
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $shouldIgnore = false;

        switch ($error->getIdentifier()) {
            case 'function.alreadyNarrowedType':
                // For an `assert()` that the DocBlocks say cannot fail.
                if (\class_exists(FunctionCallExpressionNode::class) && $node instanceof FunctionCallExpressionNode) {
                    // Unwrap for PHPStan >= 2.2.8
                    $node = $node->getOriginalNode();
                }
                if ($node instanceof FuncCall) {
                    $nameNode = $node->name;
                    if ($nameNode instanceof Name && $nameNode->name === 'assert') {
                        $shouldIgnore = true;
                    }
                }
                break;
            case 'instanceof.alwaysTrue':
                // For `instanceof` within an `assert()` that the DocBlocks say cannot fail.
                $functionCallStack = $scope->getFunctionCallStack();
                if (isset($functionCallStack[0]) && $functionCallStack[0]->getName() === 'assert') {
                    $shouldIgnore = true;
                }
                break;
        }

        return $shouldIgnore;
    }
}
