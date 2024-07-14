<?php

namespace GregPriday\CopyTree\Views;

class FileTreeView
{
    public static function render(array $files): string
    {
        $tree = self::buildTree($files);

        return ".\n".self::renderTree($tree);
    }

    private static function buildTree(array $files): array
    {
        $tree = [];
        foreach ($files as $file) {
            $path = explode('/', $file['path']);
            $current = &$tree;
            foreach ($path as $part) {
                if (! isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        return $tree;
    }

    private static function renderTree(array $tree, string $prefix = ''): string
    {
        $output = '';
        $keys = array_keys($tree);
        $lastKey = end($keys);

        foreach ($tree as $name => $subtree) {
            $isLast = ($name === $lastKey);
            $output .= $prefix.($isLast ? '└── ' : '├── ').$name."\n";

            if (! empty($subtree)) {
                $newPrefix = $prefix.($isLast ? '    ' : '│   ');
                $output .= self::renderTree($subtree, $newPrefix);
            }
        }

        return $output;
    }
}
