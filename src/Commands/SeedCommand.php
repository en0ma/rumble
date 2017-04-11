<?php
namespace en0ma\Drone\Commands;

use en0ma\Drone\Resolver;
use Aws\DynamoDB\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends Command
{
    use Resolver;

    private $directory = 'seeds';
    
    protected function configure()
    {
        $this
            ->setName('seed')
            ->setDescription('Seeds dynamoDb tables with sample data.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $classes = $this->getClasses($this->directory);
            $this->runSeeder($classes);

        } catch(\Exception $e) {
            echo "Seed Error: {$e->getMessage()}";
            exit();
        }
    }

    /**
     * Handle the "seed" command.
     *
     * @param $classes
     */
    private function runSeeder($classes)
    {
        $dynamoDbClient =  DynamoDbClient::factory($this->getConfig());
        $transformer = new Marshaler();
        
        foreach($classes as $class) {
            $migration = new $class($dynamoDbClient, $transformer);
            $migration->seed();
        }
    }
}