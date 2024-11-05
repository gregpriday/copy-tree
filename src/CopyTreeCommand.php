<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetManager;
use GregPriday\CopyTree\Utilities\GitHubUrlHandler;
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
        $workspaceNames = $rulesetManager->getAvailableWorkspaces();

        $rulesetDescription = 'Ruleset to apply. Available options: '.implode(', ', $rulesetNames).'. Default: auto';
        $workspaceDescription = 'Workspace to use. Available options: '.implode(', ', $workspaceNames);

        $this
            ->setName('app:copy-tree')
            ->setDescription('Copies the directory tree to the clipboard and optionally displays it.')
            ->setHelp('This command copies the directory tree to the clipboard by default. You can also display the tree in the console or skip copying to the clipboard.')
            ->addArgument('path', InputArgument::OPTIONAL, 'The directory path or GitHub URL', getcwd())
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree.', 10)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Outputs to a file instead of the clipboard.')
            ->addOption('display', 'i', InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, $rulesetDescription, 'auto')
            ->addOption('only-tree', 't', InputOption::VALUE_NONE, 'Include only the directory tree in the output, not the file contents.')
            ->addOption('filter', 'f', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filter files using glob patterns on the relative path. Can be specified multiple times.')
            ->addOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear the GitHub repository cache and exit.')
            ->addOption('workspace', 'w', InputOption::VALUE_OPTIONAL, $workspaceDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
        $workspace = $input->getOption('workspace');

        try {
            // Handle GitHub URLs
            $githubHandler = null;
            if (GitHubUrlHandler::isGitHubUrl($path)) {
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
                $ruleset = $rulesetManager->getRuleset($rulesetOption, $workspace);
            }

            // Execute the copy tree operation
            $executor = new CopyTreeExecutor(
                $input->getOption('only-tree')
            );

            $result = $executor->execute($ruleset);

            // Handle output
            $outputManager = new OutputManager(
                $input->getOption('display'),
                $input->getOption('output')
            );

            $outputManager->handleOutput($result, $io);

            // Clean up temporary files if we cloned a repository
            if ($githubHandler) {
                $githubHandler->cleanup();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Clean up if there was an error
            if (isset($githubHandler)) {
                $githubHandler->cleanup();
            }

            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function getWorkspace(string $workspaceName, RulesetManager $rulesetManager): ?array
    {
        if (! $rulesetManager->workspaceExists($workspaceName)) {
            throw new \InvalidArgumentException(sprintf('Workspace "%s" not found.', $workspaceName));
        }

        return $rulesetManager->getWorkspace($workspaceName);
    }
}
