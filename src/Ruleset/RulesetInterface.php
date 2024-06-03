<?php

namespace GregPriday\CopyTree\Ruleset;

interface RulesetInterface
{
    public function shouldIncludeDirectory(string $directory): bool;
    public function shouldIncludeFile(string $file): bool;
}
