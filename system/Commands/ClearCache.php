<?php

declare(strict_types=1);

namespace SlimEdge\Commands;

use SlimEdge\Paths;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ClearCache extends Command
{
    public function __construct()
    {
        $this->setName('clear:cache');
        $this->setDescription('Clear cache files');
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption("force-delete", "f", InputOption::VALUE_NONE, "Force delete");
        $this->addOption("silent", "s", InputOption::VALUE_NONE, "Silent output");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultOutput = $output;
        if($input->getOption('silent')) {
            $output = new NullOutput;
        }

        $pattern = Paths::Cache . '/*';
        $files = [];
        foreach(rglob($pattern) as $filepath) {
            if(is_file($filepath)) {
                $files[] = $filepath;
            }
        }

        if(empty($files)) {
            $output->writeln("No files were deleted.");
            return Command::SUCCESS;
        }

        $message = "<fg=cyan>" . count($files) . "</> file(s) will be deleted.\nContinue with this action? ";

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($message, false);
        if (!$input->getOption("force-delete") && $helper && !$helper->ask($input, $defaultOutput, $question)) {
            $output->writeln("No files were deleted.");
            return Command::SUCCESS;
        }

        $successCount = 0;
        foreach($files as $filepath) {
            $path = substr($filepath, strlen(STORAGE_PATH));
            $output->write("<fg=yellow>[Deleting]</> {$path}");
            if(unlink($filepath)) {
                $output->writeln(" <fg=green>[Success]</>");
                $successCount++;
            }
            else {
                $output->writeln(" <fg=red>[Failed]</>");
            }
        }

        $output->writeln('');
        $output->writeln("<fg=cyan>$successCount</> of <fg=cyan>" . count($files) . "</> file(s) deleted.");

        return Command::SUCCESS;
    }
}