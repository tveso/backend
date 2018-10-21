<?php
/**
 * Date: 09/08/2018
 * Time: 18:07
 */

namespace App\Jobs\UpdateTmdbJobs;


use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Services\PeopleService;
use App\Services\TheMovieDb\TheMovieDbClient;
use App\Services\TheMovieDb\TmdbMovieService;
use App\Services\TheMovieDb\TmdbPeopleService;
use App\Util\FindQueryBuilder;
use App\Util\PipelineBuilder\PipelineBuilder;
use MongoDB\UpdateResult;

class UpdatePeopleJob
{
    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * @var
     */
    private $themoviedb;
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;
    /**
     * @var TmdbPeopleService
     */
    private $tmdbPeopleService;

    /**
     * UpdateMoviesJob constructor.
     * @param EntityManager $entityManager
     * @param TmdbPeopleService $tmdbPeopleService
     * @param TheMovieDbClient $theMovieDbClient
     * @param UpdateSearchFieldJob $updateSearchFieldJob
     * @param PeopleService $peopleService
     */
    public function __construct(EntityManager $entityManager, TmdbPeopleService $tmdbPeopleService,
                                TheMovieDbClient $theMovieDbClient, UpdateSearchFieldJob $updateSearchFieldJob,
                                PeopleService $peopleService)
    {
        $this->entityManager = $entityManager;
        $this->themoviedb = $theMovieDbClient;
        $this->updateSearchFieldJob = $updateSearchFieldJob;
        $this->tmdbPeopleService = $tmdbPeopleService;
    }



    private function changes(?string $startDate, ?string $endDate)
    {
        $page = 1;
        $totalPages = 2;
        $result = [];
        $params = [];
        if(!is_null($startDate)){
            $params['start_date'] = $startDate;
        }
        if(!is_null($endDate)){
            $params['end_date'] = $endDate;
        }
        while($page<=$totalPages){
            $params['page'] = $page;
            $request = $this->themoviedb->request('people/changes', $params);
            $request = json_decode($request, 1);
            $totalPages = $request["total_pages"];
            echo "$page Pagina   de $totalPages\n";
            $page = $page+1;
            $result =array_merge($result, array_filter($request['results'], function($a){
                    return $a['adult'] === false;
                }));
        }

        return $result;
    }

    public function updateFromLastPersonId()
    {
        $lastEntity = $this->entityManager->findOneBy([], 'people', ['sort'=> ['id'=> -1]]);
        $lastId = 1;
        if(!is_null($lastId)){
            $lastId = $lastEntity["id"]+1;
        }
        $lastTmdbId = $this->tmdbPeopleService->latest()["id"];
        echo "De $lastId a $lastTmdbId";
        for($i=$lastId;$i<=$lastTmdbId;$i++){
            try{
                $personDetails = $this->getPersonDetails($i);
            } catch (\Exception $e){
                continue;
            }
            $personDetails = $this->updateSearchField($personDetails);
            $this->insertOrUpdate($personDetails);
            echo "Actualizado persona {$personDetails["name"]}\n";
        }
    }


    public function getPersonDetails($id)
    {
        $tmdbData = $this->tmdbPeopleService->get($id);

        return $tmdbData;
    }






    private function insertOrUpdate($personDetails)
    {
        $personDetails["_id"] = $personDetails["id"];
        $personDetails["type"] = "person";

        $this->entityManager->insertOfUpdate($personDetails, 'people');
    }

    private function updateSearchField($entity)
    {
        $name = $entity['name'];
        $name = UpdateSearchFieldJob::prepareString($name);
        for($i=1; $i<=strlen($name); $i++){
            $substr = utf8_encode(substr($name, 0, $i));
            $entity['search_title'][] = $substr;
        }

        return $entity;
    }
}