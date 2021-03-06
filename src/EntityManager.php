<?php
/**
 * Date: 09/07/2018
 * Time: 21:12
 */

namespace App;



use MongoDB\BSON\ObjectId;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Query;
use MongoDB\UpdateResult;

class EntityManager
{


    private $client;
    private $datasbase;
    private $databaseName;

    public function __construct(\MongoDB\Client $client, string $databaseName = 'mymoviedb')
    {
        $this->client = $client;
        $this->datasbase = $client->{$databaseName};
        $this->databaseName = $databaseName;
    }

    public function setDatabaseName(string $name)
    {
        $this->databaseName = $name;

        return $this;
    }

    /**
     * @param array $query
     * @param string $collectionName
     * @param array $options
     * @return Cursor
     */
    public function find(array $query, string $collectionName, array $options = [])
    {

        $element = $this->getCollection($collectionName)->find($query,$options);

        return $element;
    }

    /**
     * @param string $id
     * @param string $collectionName
     * @return array|null|object
     */
    public function findOnebyId($id, string $collectionName)
    {
        $element = $this->getCollection($collectionName)->findOne(['_id'=>$id]);

        return $element;
    }

    public function getCollection(string $collection)
    {
        return $this->datasbase->{$collection};
    }

    /**
     * @param Query $query
     * @param $collection
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function executeQuery(Query $query, $collection)
    {
        $manager = $this->client->getManager();

        return $manager->executeQuery($this->databaseName.".".$collection,$query);
    }

    public function replace($value, string $collection) : UpdateResult
    {
        if(!isset($value["_id"])) {
            $value["_id"] = new ObjectId();
        }
        $id = $value["_id"];
        return $this->getCollection($collection)->replaceOne(["_id"=>$id],$value,["upsert"=>true]);
    }

    public function update(array $query, array $value, string $collection, array $options = []) : UpdateResult
    {
        return $this->getCollection($collection)->updateMany($query, $value, $options);
    }

    public function insert(array $document, string $collection)
    {
        return $this->getCollection($collection)->insertOne($document);
    }

    public function insertOfUpdate(array $document, string $collection)
    {
        $element = $this->findOnebyId($document["_id"],$collection);
        if(is_null($element)){
            return $this->insert($document,$collection);
        }

        return $this->update(["_id"=> $document["id"]], ['$set'=> $document], $collection);
    }

    public function insertMany(array $documents, string $collection)
    {
        return $this->getCollection($collection)->insertMany($documents);
    }

    /**
     * @param array $query
     * @param string $collectionName
     * @param array $opts
     * @return array|null|object
     */
    public function findOneBy(array $query, string $collectionName, array $opts = [])
    {
        $element = $this->getCollection($collectionName)->findOne($query, $opts);

        return $element;
    }

    /**
     * @param array $value
     * @param string $collectionName
     * @return \MongoDB\DeleteResult
     */
    public function delete($value,string $collectionName) : DeleteResult
    {
        return $this->getCollection($collectionName)->deleteMany($value);
    }

    public function aggregate(array $query, array $opts, string $collectionName)
    {
        return $this->getCollection($collectionName)->aggregate($query, $opts);
    }

}