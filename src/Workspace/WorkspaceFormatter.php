<?php

namespace GregPriday\CopyTree\Workspace;

class WorkspaceFormatter
{
    public function formatOutput(string $output, string $format): string
    {
        return match($format) {
            'claude' => $this->formatForClaude($output),
            'gpt' => $this->formatForGPT($output),
            default => $output,
        };
    }

    private function formatForClaude(string $output): string
    {
        return "<document>\n" . $output . "\n</document>";
    }

    private function formatForGPT(string $output): string
    {
        return "```\n" . $output . "\n```";
    }
}
