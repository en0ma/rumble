<?php

namespace Matasar\Bundle\Rumble;

/**
 * @author Lawrence Enehizena <lawstands@gmail.com>
 */
abstract class Seeder
{
    /**
     * DynamoDb table
     */
    private $table;

    /**
     * Aws dynamoDBClient
     */
    private $dynamoDBClient;

    /**
     * Aws Marshaler(makes formating data easy)
     */
    private $marshaler;

    /**
     * dynamoDB table items
     */
    private $items = [];

    /**
     * Seeder constructor.
     *
     * @param $dynamoDBClient
     * @param $marshaler
     */
    function __construct($dynamoDBClient, $marshaler)
    {
        $this->dynamoDBClient = $dynamoDBClient;
        $this->marshaler = $marshaler;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    protected function table($name)
    {
        $this->table = $name;

        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
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
     * Verify that the TableName param is set.
     * This is a mandatory param, just like the hash key.
     *
     * @throws \Exception
     */
    private function isTableNameSet()
    {
        if (!$this->table) {
            throw new \Exception('Error: DynamoDB requires table name to be specified.');
        }
    }

    /**
     * Verify that at least one item is added.
     *
     * @throws \Exception
     */
    private function atLeastOneItemExist()
    {
        if (count($this->items) == 0) {
            throw new \Exception('Error: No data to be seeded.');
        }
    }

    /**
     * @param $table
     *
     * @throws \Exception
     */
    private function tableExist($table)
    {
        $result = $this->dynamoDBClient->listTables();

        if (!in_array($table, $result['TableNames'])) {
            throw new \Exception("Error: {$table} table those not exist.");
        }
    }

    /**
     * Check if there are multiple items.
     */
    private function isBatchRequest()
    {
        return (count($this->items) > 1);
    }

    /**
     * Check to make sure batch items is not over limit (100).
     *
     * @throws \Exception
     */
    private function validateBatchItemsLimit()
    {
        if (count($this->items) > 100) {
            throw new \Exception('Maximum items that can be bacthed add is 100, limit exceeded.');
        }
    }

    /**
     * Show completion message, when migration is successful.
     */
    private function displayCompletionMessage()
    {
        $className = get_class($this);
        echo "{$className} seeded successfully". PHP_EOL;

        return true;
    }

    /**
     * Add a new item(s) to the specified dynamodb table.
     * If a single item is added, a simple putItem is used.
     * If multiple items are added, then the batchWriteItem is used.
     * NB: batchWriteItem can take maximum of 100 items per call.
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function save()
    {
        $this->isTableNameSet();
        $this->atLeastOneItemExist();
        $this->tableExist($this->table);

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

            $this->dynamoDBClient->batchWriteItem(
                [
                    'RequestItems' => $items   
                ]
            );
        } else {
            $this->dynamoDBClient->putItem(
                [
                    'TableName' => $this->table,
                    'Item' => $this->items[0]
                ]
            );
        }

        return $this->displayCompletionMessage();
    }

    /**
     * Force seed files to define the seed method.
     */
    protected abstract function seed();
}
