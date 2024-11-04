<?php

namespace GregPriday\CopyTree;

use GregPriday\CopyTree\Ruleset\RulesetManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to copy directory tree structure and file contents to clipboard or file.
 *
 * Allows filtering files based on rulesets, setting depth, and controlling output options.
 */
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
            ->addArgument('path', InputArgument::OPTIONAL, 'The directory path', getcwd())
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Maximum depth of the tree.', 10)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Outputs to a file instead of the clipboard.')
            ->addOption('display', 'i', InputOption::VALUE_NONE, 'Display the output in the console.')
            ->addOption('ruleset', 'r', InputOption::VALUE_OPTIONAL, $rulesetDescription, 'auto')
            ->addOption('only-tree', 't', InputOption::VALUE_NONE, 'Include only the directory tree in the output, not the file contents.')
            ->addOption('filter', 'f', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filter files using glob patterns on the relative path. Can be specified multiple times.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path') ?? getcwd();
        $filters = $input->getOption('filter');
        $rulesetOption = $input->getOption('ruleset');

        try {
            $rulesetManager = new RulesetManager($path, $io);

            if (! empty($filters)) {
                // Convert single string filter to array for backwards compatibility
                $filters = is_array($filters) ? $filters : [$filters];
                $ruleset = $rulesetManager->createRulesetFromGlobs($filters);
            } elseif ($rulesetOption === 'none') {
                $ruleset = $rulesetManager->createEmptyRuleset();
            } else {
                $ruleset = $rulesetManager->getRuleset($rulesetOption);
            }

            $executor = new CopyTreeExecutor(
                $input->getOption('only-tree'),
            );

            $outputManager = new OutputManager(
                $input->getOption('display'),
                $input->getOption('output')
            );

            $result = $executor->execute($ruleset);
            $outputManager->handleOutput($result, $io);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
