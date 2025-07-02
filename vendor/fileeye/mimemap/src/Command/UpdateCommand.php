<?php declare(strict_types=1);

namespace FileEye\MimeMap\Command;

use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use FileEye\MimeMap\Map\MimeMapInterface;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\MapUpdater;

/**
 * A Symfony application command to update the MIME type to extension map.
 */
class UpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('update')
            ->setDescription('Updates the MIME-type-to-extension map. Executes the commands in the script file specified by --script, then writes the map to the PHP file where the PHP --class is defined.')
            ->addOption(
                'script',
                null,
                InputOption::VALUE_REQUIRED,
                'File name of the script containing the sequence of commands to execute to build the default map.',
                MapUpdater::getDefaultMapBuildFile(),
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                'The fully qualified class name of the PHP class storing the map.',
                MapHandler::DEFAULT_MAP_CLASS,
            )
            ->addOption(
                'diff',
                null,
                InputOption::VALUE_NONE,
                'Report updates.',
            )
            ->addOption(
                'fail-on-diff',
                null,
                InputOption::VALUE_NONE,
                'Exit with an error when a difference is found. Map will not be updated.',
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $updater = new MapUpdater();
        $updater->selectBaseMap(MapUpdater::DEFAULT_BASE_MAP_CLASS);

        $scriptFile = $input->getOption('script');
        if (!is_string($scriptFile)) {
            $io->error('Invalid value for --script option.');
            return (2);
        }

        $mapClass = $input->getOption('class');
        if (!is_string($mapClass)) {
            $io->error('Invalid value for --class option.');
            return (2);
        }

        $diff = $input->getOption('diff');
        assert(is_bool($diff));
        $failOnDiff = $input->getOption('fail-on-diff');
        assert(is_bool($failOnDiff));

        // Executes on the base map the script commands.
        $contents = file_get_contents($scriptFile);
        if ($contents === false) {
            $io->error('Failed loading update script file ' . $scriptFile);
            return (2);
        }

        $commands = Yaml::parse($contents);
        if (!is_array($commands)) {
            $io->error('Invalid update script file ' . $scriptFile);
            return (2);
        }

        /** @var list<array{0: string, 1: string, 2: array<mixed>}> $commands */
        foreach ($commands as $command) {
            $output->writeln("<info>{$command[0]} ...</info>");
            try {
                $callable = [$updater, $command[1]];
                assert(is_callable($callable));
                $errors = call_user_func_array($callable, $command[2]);
                if (is_array($errors) && !empty($errors)) {
                    /** @var list<string> $errors */
                    foreach ($errors as $error) {
                        $output->writeln("<comment>$error.</comment>");
                    }
                }
            } catch (\Exception $e) {
                $io->error($e->getMessage());
                return(1);
            }
        }

        // Load the map to be changed.
        /** @var class-string<MimeMapInterface> $mapClass */
        MapHandler::setDefaultMapClass($mapClass);
        $current_map = MapHandler::map();

        // Check if anything got changed.
        $write = true;
        if ($diff) {
            $write = false;
            foreach ([
                't' => 'MIME types',
                'a' => 'MIME type aliases',
                'e' => 'extensions',
            ] as $key => $desc) {
                try {
                    $output->writeln("<info>Checking changes to {$desc} ...</info>");
                    $this->compareMaps($current_map, $updater->getMap(), $key);
                } catch (\RuntimeException $e) {
                    $output->writeln("<comment>Changes to {$desc} mapping:</comment>");
                    $output->writeln($e->getMessage());
                    $write = true;
                }
            }
        }

        // Fail on diff if required.
        if ($write && $diff && $failOnDiff) {
            $io->error('Changes to mapping detected and --fail-on-diff requested, aborting.');
            return(2);
        }

        // If changed, save the new map to the PHP file.
        if ($write) {
            try {
                $updater->writeMapToPhpClassFile($current_map->getFileName());
                $output->writeln('<comment>Code updated.</comment>');
            } catch (\RuntimeException $e) {
                $io->error($e->getMessage() .  '.');
                return(2);
            }
        } else {
            $output->writeln('<info>No changes to mapping.</info>');
        }

        // Reset the new map's map array.
        $updater->getMap()->reset();

        return(0);
    }

    /**
     * Compares two type-to-extension maps by section.
     *
     * @param MimeMapInterface $old_map
     *   The first map to compare.
     * @param MimeMapInterface $new_map
     *   The second map to compare.
     * @param string $section
     *   The first-level array key to compare: 't' or 'e' or 'a'.
     *
     * @throws \RuntimeException with diff details if the maps differ.
     *
     * @return bool
     *   True if the maps are equal.
     */
    protected function compareMaps(MimeMapInterface $old_map, MimeMapInterface $new_map, string $section): bool
    {
        $old_map->sort();
        $new_map->sort();
        $old = $old_map->getMapArray();
        $new = $new_map->getMapArray();

        $factory = new Factory;
        $comparator = $factory->getComparatorFor($old[$section], $new[$section]);
        try {
            $comparator->assertEquals($old[$section], $new[$section]);
            return true;
        } catch (ComparisonFailure $failure) {
            $old_string = var_export($old[$section], true);
            $new_string = var_export($new[$section], true);
            if (class_exists('\SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder')) {
                $differ = new Differ(new UnifiedDiffOutputBuilder("--- Removed\n+++ Added\n"));
                throw new \RuntimeException($differ->diff($old_string, $new_string));
            } else {
                throw new \RuntimeException(' ');
            }
        }
    }
}
