<?php
namespace App\Extensions;

class ProductEs extends \Jfxy\Elasticsearch\Builder
{

    public $index = 'products';

    protected function clientBuilder()
    {
        $client = \Elasticsearch\ClientBuilder::create();

        $client->setHosts(['http://139.196.157.136:9200']);

        $client = $client->build();

        $this->setClient($client);
    }
}
