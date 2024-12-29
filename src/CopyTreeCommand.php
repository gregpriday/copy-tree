<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Filters\FilterConfiguration;
use GregPriday\CopyTree\Filters\FilterPipelineConfiguration;
use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Utilities\Git\GitHubUrlHandler;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyTreeCommand extends Command
{
    protected static string $defaultName = 'app:copy-tree';

    private ?GitHubUrlHandler $githubHandler = null;

    protected function configure(): void
    {
        $rulesetManager = new RulesetManager(getcwd());
        $rulesetNames = $rulesetManager->getAvailableRulesets();
        $rulesetDescription = 'Ruleset to apply. Available options: '.implode(', ', $rulesetNames).'. Default: auto';

        $this
            ->setName('app:copy-tree')
            ->setDescription('Copies the directory tree to the clipboard and optionally displays it.')
            ->setHelp('This command copies the directory tree to the clipboard by default. You can also display the tree in the console or skip copying to the clipboard.')

            // Main argument
            ->addArgument('path', InputArgument::OPTIONAL, 'The directory path or GitHub URL', getcwd())

            // Core functionality options
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree.', 10)
            ->addOption('max-lines', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of lines to show per file. Use 0 for unlimited.', 0)
            ->addOption('only-tree', 't', InputOption::VALUE_NONE, 'Include only the directory tree in the output, not the file contents.')

            // Filtering options
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, $rulesetDescription, 'auto')
            ->addOption('filter', 'f', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filter files using glob patterns on the relative path. Can be specified multiple times.')
            ->addOption('ai-filter', 'a', InputOption::VALUE_OPTIONAL, 'Filter files using AI based on a natural language description', false)

            // Git based filtering
            ->addOption('modified', 'm', InputOption::VALUE_NONE, 'Only include files that have been modified since the last commit')
            ->addOption('changes', 'c', InputOption::VALUE_REQUIRED, 'Filter for files changed between two commits in format "commit1:commit2" (e.g. abc123:def456)')

            // Output options
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Outputs to a file. If no filename is provided, creates file in ~/.copytree/files/')
            ->addOption('display', 'i', InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('stream', 's', InputOption::VALUE_NONE, 'Stream output directly (useful for piping)')
            ->addOption('as-reference', 'p', InputOption::VALUE_NONE, 'Copy a reference to a temporary file instead of copying the content directly.')

            // GitHub-related options
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Do not use or keep cached GitHub repositories.')
            ->addOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear the GitHub repository cache and exit.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (PHP_OS_FAMILY !== 'Darwin') {
            throw new RuntimeException('This package only supports MacOS.');
        }

        $io = new SymfonyStyle($input, $output);

        try {
            if ($input->getOption('clear-cache')) {
                return $this->handleCacheClearing($io);
            }

            $path = $this->resolvePath($input, $io);
            $filterConfig = $this->createFilterConfiguration($input);
            $pipelineConfig = $this->createPipelineConfiguration($path, $filterConfig, $io);

            $result = $this->executeOperation($input, $io, $pipelineConfig);

            $this->handleOutput($input, $io, $result);
            $this->cleanup($input);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->handleError($e, $input, $io);
            return Command::FAILURE;
        }
    }

    private function handleCacheClearing(SymfonyStyle $io): int
    {
        try {
            GitHubUrlHandler::cleanCache();
            $io->success('GitHub repository cache cleared successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolvePath(InputInterface $input, SymfonyStyle $io): string
    {
        $path = $input->getArgument('path') ?? getcwd();

        if (GitHubUrlHandler::isGitHubUrl($path)) {
            return $this->handleGitHubPath($path, $input, $io);
        }

        return $path;
    }

    private function handleGitHubPath(string $url, InputInterface $input, SymfonyStyle $io): string
    {
        if ($input->getOption('modified') || $input->getOption('changes')) {
            throw new RuntimeException('Git options cannot be used with GitHub URLs');
        }

        $io->writeln('Detected GitHub URL. Cloning repository...', OutputInterface::VERBOSITY_VERBOSE);
        $this->githubHandler = new GitHubUrlHandler($url);
        return $this->githubHandler->getFiles();
    }

    private function createFilterConfiguration(InputInterface $input): FilterConfiguration
    {
        return FilterConfiguration::fromInput($input);
    }

    private function createPipelineConfiguration(
        string $path,
        FilterConfiguration $filterConfig,
        SymfonyStyle $io
    ): FilterPipelineConfiguration {
        $rulesetManager = new RulesetManager($path, $io);
        $ruleset = $this->resolveRuleset($rulesetManager, $filterConfig);

        return new FilterPipelineConfiguration($path, $filterConfig, $ruleset);
    }

    private function resolveRuleset(RulesetManager $manager, FilterConfiguration $config)
    {
        if (!empty($config->getGlobPatterns())) {
            return $manager->createRulesetFromGlobs($config->getGlobPatterns());
        }

        if ($config->getRulesetName() === 'none') {
            return $manager->createEmptyRuleset();
        }

        return $manager->getRuleset($config->getRulesetName());
    }

    private function executeOperation(
        InputInterface $input,
        SymfonyStyle $io,
        FilterPipelineConfiguration $config
    ): array {
        $executor = new CopyTreeExecutor(
            config: $config,
            onlyTree: $input->getOption('only-tree'),
            io: $io
        );

        return $executor->execute();
    }

    private function handleOutput(InputInterface $input, SymfonyStyle $io, array $result): void
    {
        $outputOption = $input->getOption('output');
        $useOutput = !empty($outputOption);
        $outputFile = $useOutput ? (reset($outputOption) ?: '') : null;

        $outputManager = new OutputManager(
            $input->getOption('display'),
            $outputFile,
            $input->getOption('stream'),
            $input->getOption('as-reference')
        );

        $outputManager->handleOutput($result, $io);
    }

    private function cleanup(InputInterface $input): void
    {
        if ($this->githubHandler && $input->getOption('no-cache')) {
            $this->githubHandler->cleanup();
        }
    }

    private function handleError(\Exception $e, InputInterface $input, SymfonyStyle $io): void
    {
        if ($this->githubHandler && $input->getOption('no-cache')) {
            $this->githubHandler->cleanup();
        }

        $io->error($e->getMessage());
    }
}
