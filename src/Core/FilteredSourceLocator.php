<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class FilteredSourceLocator implements SourceLocator
{
    /**
     * @param array<string> $filterPatterns
     */
    public function __construct(private readonly SourceLocator $wrappedLocator, private readonly array $filterPatterns) {}

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        $reflection = $this->wrappedLocator->locateIdentifier($reflector, $identifier);

        if (! $reflection instanceof ReflectionClass) {
            return null;
        }

        if ($this->shouldExclude($reflection->getFileName())) {
            return null;
        }

        return $reflection;
    }

    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        $reflections = $this->wrappedLocator->locateIdentifiersByType($reflector, $identifierType);

        return \array_filter($reflections, fn (Reflection $reflection): bool => $reflection instanceof ReflectionClass && ! $this->shouldExclude($reflection->getFileName()));
    }

    private function shouldExclude(string $fileName): bool
    {
        foreach ($this->filterPatterns as $pattern) {
            if (\preg_match($pattern, $fileName)) {
                return false;
            }
        }

        return true;
    }
}
