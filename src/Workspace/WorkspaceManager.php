<?php

namespace GregPriday\CopyTree\Workspace;

use InvalidArgumentException;

class WorkspaceManager
{
    private string $basePath;

    private array $workspaces = [];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->loadWorkspaces();
    }

    private function loadWorkspaces(): void
    {
        $projectConfig = $this->basePath.'/.ctree/workspaces.json';
        if (file_exists($projectConfig)) {
            $this->loadWorkspaceFile($projectConfig);
        }
    }

    private function loadWorkspaceFile(string $path): void
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid workspace configuration: '.json_last_error_msg());
        }

        if (isset($data['workspaces']) && is_array($data['workspaces'])) {
            $this->workspaces = array_merge($this->workspaces, $data['workspaces']);
        }
    }

    public function hasWorkspace(string $name): bool
    {
        return isset($this->workspaces[$name]);
    }

    public function getWorkspace(string $name): ?array
    {
        return $this->workspaces[$name] ?? null;
    }

    public function getAvailableWorkspaces(): array
    {
        return array_keys($this->workspaces);
    }
}
