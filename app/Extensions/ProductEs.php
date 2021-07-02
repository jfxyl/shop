<?php
namespace App\Extensions;

class ProductEs extends \Jfxy\Elasticsearch\Builder
{

    public $index = 'products';

    protected function clientBuilder()
    {
        $config = [
            'hosts' => ['http://139.196.157.136:9200'],
            'connection_retry_times' => 5,
            'connection_pool' => \Elasticsearch\ConnectionPool\StaticNoPingConnectionPool::class,
            'selector' => \Elasticsearch\ConnectionPool\Selectors\RoundRobinSelector::class,
            'serializer' => \Elasticsearch\Serializers\SmartSerializer::class,
        ];

        $client = \Elasticsearch\ClientBuilder::create();

        $client->setHosts($config['hosts'])
            ->setRetries($config['connection_retry_times'])
            ->setConnectionPool($config['connection_pool'])
            ->setSelector($config['selector'])
            ->setSerializer($config['serializer'])
            ->build();

        return $client->build();
    }
}
