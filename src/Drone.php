<?php
namespace Drone;

use Aws\DynamoDB\Marshaler;
use Aws\DynamoDb\DynamoDbClient;

class Drone implements CommandInterface
{
    private $config = [];

    /**
        Deploy drone. Lol, this is obvious. 
        Drone use the console dependency to manage cli headaches.
    **/
    public function deploy()
    {
        $console = new Console\Runner();

        $console->registerAll([
            'migrate' => $this,
            'seed' => $this,
        ]);

        try {
            $console->run();
        } catch(Console\Exception\CommandNotFoundException $e) {
            echo "{$e->getMessage()}: is not a valid instruction.".PHP_EOL;
            exit();
        }
    }

    /**
        Console cli dependency handles boostraping
        and exexutes this (exexute) method. 
        This is more like the entry point.
    **/
    public function execute(Args $args)
    {
        $this->setConfig();
        $command = $args->getCommands()[0];

        if ($command == 'migrate') {
            $dir = dirname(__FILE__) . '/Migrations';
            if (!file_exists($dir)) {
                echo 'Error: migrations folder not found.'.PHP_EOL;
                exit();
            }
            try {
                return $this->runMigration($this->getClasses($dir));
            } catch(\Exception $e) {
                echo "Error: {$e->getMessage()}".PHP_EOL;
                exit();
            }
        }

        if ($command == 'seed') {
            $dir = dirname(__FILE__) . '/Seeds';
            if (!file_exists($dir)) {
                echo "Error: seeds folder not found.".PHP_EOL;
                exit();
            }
            try {
                return $this->runSeeder($this->getClasses($dir));
            } catch(\Exception $e) {
                echo "Error: {$e->getMessage()}".PHP_EOL;
                exit();
            }
        }
    }

    /**
        Handle the "migrate" command.
    **/
    private function runMigration($classes)
    {    
        $dynamoDbClient =  DynamoDbClient::factory($this->config['dynamo_db']); 
        foreach ($classes as $class) {    
            $migration = new $class($dynamoDbClient);
            $migration->up();
        }            
    }

    /**
        Handle the "seed" command.
    **/
    private function runSeeder($classes)
    {
        $dynamoDbClient =  DynamoDbClient::factory($this->config['dynamo_db']); 
        $marshaler = new Marshaler();
        foreach($classes as $class) {
            $migration = new $class($dynamoDbClient, $marshaler);
            $migration->seed();
        }
    }

    /**
        Get class names from files in migrations/seeds directory.
        For any class found require it, so we can create an
        instance.
    **/
    private function getClasses($dir)
    {
        $dirHandler  = opendir($dir);
        $classes = [];
        while (false != ($file = readdir($dirHandler))) {
            if ($file != "." && $file != "..") {
                require_once($dir.'/'.$file); //include file
                $classes[] = $this->buildClass($file);;
            }
        }
        closedir($dirHandler);
        return $classes;
    }

    /**
        Build class names from file name. This uses an underscore (_) convention.
        Each file in eigther the migrations or seeds folder, uses an underscore naming
        convention. eg: create_me_table => CreateMeTable (ClassName)
    **/
    private function buildClass($file)
    {
        $file = basename($file, '.php'); //remove extension .php
        $fileNameParts = explode('_', $file); //remove underscore
    
        foreach ($fileNameParts as &$part) {
            $part = ucfirst($part);
        }
        return implode('', $fileNameParts);
    }

    /**
        Set's the configuration params used by dynamodb. 
        This is picked up from the .credential.yml file.
    **/
    private function setConfig()
    {
        if (!file_exists(dirname(__FILE__).'/.credential.yml')) {
            die('The .credentials.yml fddddile could not be found.');
        }
        $credentials = Yaml::parse(file_get_contents(dirname(__FILE__).'/.credential.yml'));

        $this->config['dynamo_db']  = $credentials['aws']['dynamo_db']['local'];  //not sure how this will be set
    }

    public function getDescription()
    {
        return 'Migration tool';
    }
}

$drone = new Drone();
$drone->deploy();