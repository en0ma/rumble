<?php

namespace Matasar\Bundle\Rumble\Command;

use Matasar\Bundle\Rumble\Resolver;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Lawrence Enehizena <lawstands@gmail.com>
 */
class MigrateCommand extends Command
{
    use Resolver;

    /**
     * @var DynamoDbClient
     */
    protected $dynamoDBClient;

    /**
     * @var string
     */
    private $directory = 'migrations';

    protected function configure()
    {
        $this->setName('rumble:migrate')
            ->setDescription('Creates and versions dynamoDB tables.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $classes = $this->getClasses($this->directory);
            $this->runMigration($classes);

        } catch(\Exception $e) {
            echo "Migration Error: {$e->getMessage()}".PHP_EOL;
            exit();
        }
    }

    /**
     * Handle the "migrate" command.
     *
     * @param array $classes
     *
     * @throws \Exception
     */
    private function runMigration(array $classes)
    {
        $this->dynamoDBClient = DynamoDbClient::factory($this->getConfig());

        if (!$this->isMigrationsTableExist())
            $this->createMigrationTable();

        $ranMigrations = $this->getRanMigrations();
        $pendingMigrations = $this->getPendingMigrations($classes, $ranMigrations);

        if (count($pendingMigrations) == 0) {
            echo "Nothing new to migrate";
            return;
        }

        foreach ($pendingMigrations as $pendingMigration) {
            $migration = new $pendingMigration($this->dynamoDBClient);
            $migration->up();
            $this->addToRanMigrations($pendingMigration);
        }
    }

    /**
     * @param array $classes
     * @param array $ranMigrations
     *
     * @return mixed
     */
    private function getPendingMigrations(array $classes, array $ranMigrations)
    {
        foreach ($ranMigrations as $ranMigration) {
            $key = array_search($ranMigration, $classes);
            if ($key !== FALSE)
                unset($classes[$key]);
        }
        return $classes;
    }

    /**
     * @return array
     */
    private function getRanMigrations()
    {
        $result =  $this->dynamoDBClient->scan([
            'TableName' => 'migrations'
        ]);

        $marsh = new Marshaler();
        $ranMigrations = [];

        foreach ($result->getAll()['Items'] as $item) {
            $ranMigrations[] = $marsh->unmarshalItem($item)['migration'];
        }
        return $ranMigrations;
    }

    /**
     * @return bool
     */
    private function isMigrationsTableExist()
    {
        $tables = $this->dynamoDBClient->listTables();
        return in_array('migrations', $tables['TableNames']);
    }

    private function createMigrationTable()
    {
        $this->dynamoDBClient->createTable([
            'TableName' => 'migrations',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'migration',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'migration',
                    'KeyType'       => 'HASH'
                ]
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits'  => 1,
                'WriteCapacityUnits' => 1
            ]
        ]);
    }

    /**
     * @param string $migration
     */
    private function addToRanMigrations($migration)
    {
        $this->dynamoDBClient->putItem([
            'TableName' => 'migrations',
            'Item' => [
                'migration' => ['S' => $migration]
            ]
        ]);
    }
}
