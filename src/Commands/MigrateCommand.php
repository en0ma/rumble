<?php
namespace en0ma\Drone\Commands;

use en0ma\Drone\Resolver;
use Aws\DynamoDb\DynamoDbClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    use Resolver;

    private $directory = 'migrations';

    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Creates and versions dynamoDB tables.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $classes = $this->getClasses($this->directory);
            $this->runMigration($classes);

        } catch(\Exception $e) {
            echo "Migration Error: {$e->getMessage()}";
            exit();
        }
    }

    /**
     * Handle the "migrate" command.
     *
     * @param $classes
     */
    private function runMigration($classes)
    {
        $dynamoDbClient = DynamoDbClient::factory($this->getConfig());

        foreach ($classes as $class) {
            $migration = new $class($dynamoDbClient);
            $migration->up();
        }
    }
}