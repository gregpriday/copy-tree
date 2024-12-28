<?php

namespace GregPriday\CopyTree;

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

        // Handle cache clearing
        if ($input->getOption('clear-cache')) {
            try {
                GitHubUrlHandler::cleanCache();
                $io->success('GitHub repository cache cleared successfully');

                return Command::SUCCESS;
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }
        }

        $path = $input->getArgument('path') ?? getcwd();
        $filters = $input->getOption('filter');
        $rulesetOption = $input->getOption('ruleset');
        $noCache = $input->getOption('no-cache');
        $modifiedOnly = $input->getOption('modified');
        $changes = $input->getOption('changes');

        // Add after getting the options
        if ($modifiedOnly && $changes) {
            throw new RuntimeException('The --modified and --changes options cannot be used together');
        }

        try {
            // Handle GitHub URLs
            $githubHandler = null;
            if (GitHubUrlHandler::isGitHubUrl($path)) {
                if ($modifiedOnly) {
                    throw new RuntimeException('The --modified option cannot be used with GitHub URLs');
                }
                if ($changes) {
                    throw new RuntimeException('The --changes option cannot be used with GitHub URLs');
                }

                $io->writeln('Detected GitHub URL. Cloning repository...', OutputInterface::VERBOSITY_VERBOSE);
                $githubHandler = new GitHubUrlHandler($path);
                $path = $githubHandler->getFiles();
            }

            $rulesetManager = new RulesetManager($path, $io);

            // Determine which ruleset to use
            if (! empty($filters)) {
                $filters = is_array($filters) ? $filters : [$filters];
                $ruleset = $rulesetManager->createRulesetFromGlobs($filters);
            } elseif ($rulesetOption === 'none') {
                $ruleset = $rulesetManager->createEmptyRuleset();
            } else {
                $ruleset = $rulesetManager->getRuleset($rulesetOption);
            }

            // Get AI filter description if requested
            $aiFilterDescription = null;
            if ($input->getOption('ai-filter') !== false) {
                $aiFilterDescription = $input->getOption('ai-filter') ?: $io->ask('Enter your filtering description');
                if ($aiFilterDescription) {
                    $io->writeln('Using AI filter: '.$aiFilterDescription, OutputInterface::VERBOSITY_VERBOSE);
                }
            }

            // If using modified files, show verbose message
            if ($modifiedOnly) {
                $io->writeln('Filtering modified files since last commit...', OutputInterface::VERBOSITY_VERBOSE);
            }

            // If using changes between commits, show verbose message
            if ($changes) {
                $io->writeln('Filtering files changed between commits...', OutputInterface::VERBOSITY_VERBOSE);
            }

            // Execute the copy tree operation with filters
            $executor = new CopyTreeExecutor(
                path: $path,
                onlyTree: $input->getOption('only-tree'),
                aiFilterDescription: $aiFilterDescription,
                io: $io,
                maxLines: (int) $input->getOption('max-lines'),
                modifiedOnly: $modifiedOnly,
                changes: $changes
            );

            $result = $executor->execute($ruleset);

            // Process the output option
            $outputOption = $input->getOption('output');
            $useOutput = ! empty($outputOption);
            $outputFile = $useOutput ? (reset($outputOption) ?: '') : null;

            // Handle output
            $outputManager = new OutputManager(
                $input->getOption('display'),
                $outputFile,
                $input->getOption('stream'),
                $input->getOption('as-reference')
            );

            $outputManager->handleOutput($result, $io);

            // Only clean up if --no-cache option is set
            if ($githubHandler && $noCache) {
                $githubHandler->cleanup();
                $io->writeln('Cleaned up temporary repository files', OutputInterface::VERBOSITY_VERBOSE);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Clean up if there was an error and --no-cache was set
            if (isset($githubHandler) && $noCache) {
                $githubHandler->cleanup();
            }

            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
