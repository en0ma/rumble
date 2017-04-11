<?php 
namespace en0ma\Drone;

abstract class Seeder
{
    /**
        Dynamodb table
    **/
    private $table;

    /**
        Aws dynamodbClient
    **/
    private $dynamoDBCLient;

    /**
        Aws Marshaler(makes fomarting data easy)
    **/
    private $marshaler;

    /**
        dynamodb table items
    **/
    private $items = [];

    /**
        Set dynamodbClient and Marshaler
    **/
    function __construct($dynamoDBCLient, $marshaler)
    {
        $this->dynamoDBCLient = $dynamoDBCLient;
        $this->marshaler = $marshaler;
    }

    /**
        Set dynamodb table name.
    **/
    protected function table($name)
    {
        $this->table = $name;
        return $this;
    }

    /**
        Add a new item
    **/
    protected function addItem($data)
    {
        $attibutes = $this->marshaler->marshalItem($data);

        $item = [];
        foreach($attibutes as $attribute => $value) {
           $item[$attribute] = $value;
        }
        $this->items[] = $item;
        return $this;
    }

    /**
        Verify that the TableName param is set.
        This is a mandatory param, just like the hash key.
    **/
    private function isTableNameSet()
    {
        if (!$this->table)
            throw new \Exception('Error: DynamoDB requires table name to be specified.');
    }

    /**
        Verify that at least one item is added.
    **/
    private function atLeastOneItemExist()
    {
        if (count($this->items) == 0)
            throw new \Exception('Error: No data to be seeded.');
    }

    /**
        Check if there are multiple items.
    **/
    private function isBatchRequest()
    {
        return (count($this->items) > 1);
    }

    /**
        Check to make sure batch items is not over limit (100).
    **/
    private function validateBatchItemsLimit()
    {
        if(count($this->items) > 100)
            throw new \Exception('Maximum items that can be bacthed add is 100, limit exceeded.');
    }

    /**
        Show completion message, when migration is successful.
    **/
    private function displayCompletionMessage()
    {
        $className = get_class($this);
        echo "{$className} seeded successfully". PHP_EOL;
    }

    /**
        Add a new item(s) to the specified dynamodb table.
        If a single item is added, a simple putItem is used.
        If multiple items are added, then the batchWriteItem is used.
        NB: batchWriteItem can take maximum of 100 items per call.
    **/
    protected function save()
    {
        $this->isTableNameSet();
        $this->atLeastOneItemExist();

        if ($this->isBatchRequest()) {
            $this->validateBatchItemsLimit();

            $items = [];
            foreach ($this->items as $item) {
                $items[$this->table][] = [
                    'PutRequest' => [
                        'Item' => $item
                    ]
               ];
            }

            $this->dynamoDBCLient->batchWriteItem(
                [
                    'RequestItems' => $items   
                ]
            );
        } else {
            $this->dynamoDBCLient->putItem(
                [
                    'TableName' => $this->table,
                    'Item' => $this->items[0]
                ]
            );
        }
        return $this->displayCompletionMessage();
    }

    /**
        Force seed files to define the seed method.
    **/
    protected abstract function seed();
}
