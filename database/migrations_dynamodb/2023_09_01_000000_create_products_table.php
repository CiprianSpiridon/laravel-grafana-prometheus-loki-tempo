<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use BaoPham\DynamoDb\DynamoDbClientService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the DynamoDB client with the default connection from config
        $client = app(DynamoDbClientService::class)->getClient();

        // Create products table
        $client->createTable([
            'TableName' => 'products',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'product_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'category_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'sku',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'product_id',
                    'KeyType' => 'HASH'
                ]
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'category_id-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'category_id',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'Projection' => [
                        'ProjectionType' => 'ALL'
                    ]
                ],
                [
                    'IndexName' => 'sku-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'sku',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'Projection' => [
                        'ProjectionType' => 'ALL'
                    ]
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'products'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();

        // Delete products table
        $client->deleteTable([
            'TableName' => 'products'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'products'
        ]);
    }
};
