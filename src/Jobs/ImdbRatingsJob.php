<?php
/**
 * Date: 26/10/2018
 * Time: 17:29
 */

namespace App\Jobs;


use App\EntityManager;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Stopwatch\Stopwatch;

class ImdbRatingsJob
{
    const IMDBRATING_DOWNLOAD_URL = 'https://datasets.imdbws.com/title.ratings.tsv.gz';
    const DUMPS_PATHS = 'dumps';
    private $fileSize;
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    public function updateBdEpisodeFields()
    {
        $query = ['type' => 'tvshow', 'seasons.episodes._id' => ['$exists' => false]];
        $shows = $this->entityManager->find($query, 'movies', ['sort' => ['popularity' => -1]]);
        foreach ($shows as $ks=>$show) {
            foreach ($show['seasons'] as $kss=>$season) {
                try{
                foreach ($season['episodes'] as $ke=>$episode) {
                    try{
                        $show['seasons'][$kss]['episodes'][$ke]['_id'] = new ObjectId();
                    } catch (\Exception $e){
                        continue;
                    }
                }
                } catch (\Exception $e){
                    continue;
                }
            }
            $this->entityManager->replace($show, 'movies');
            echo "{$show['name']} Actualizada\n";
        }
    }


    public function updateUnratedShows()
    {
        $sw = new Stopwatch();
        while(true){
            $shows = $this->entityManager->find(["tconst"=> ['$gt'=> 'tt0126403']], 'imdbratings');
            $counter = 0;
            $duration = 0;
            try{
                foreach ($shows as $show) {
                    ++$counter;
                    $ev = $sw->start('once');
                    $replaced = [];
                    $replaced["rating"] = ["averageRating" => $show["averageRating"], "numVotes" => $show['numVotes']];
                    $replaced["updated_at"] = (new \DateTime())->format("Y-m-d");
                    $replaced = $this->entityManager->update(['_id'=> $show['tconst']], ['$set'=> $replaced], 'movies');
                    $ev->stop();
                    $duration +=$ev->getDuration();
                    $sw->reset();
                    if($replaced->getModifiedCount()>0){
                        echo $show["tconst"]. " actualizado el rating. ".(intval($duration))."/$counter \n";
                    }
                }
            } catch (\Exception $e){
                dump($e->getMessage());

                continue;
            }
            break;
        }
    }
    public function action()
    {
        $this->downloadFile()->decompressFile()->importToMongo();
        $this->updateUnratedShows();
    }

    private function downloadFile() : self
    {
        echo "Downloaded Imdb file...\n";
        file_put_contents(self::DUMPS_PATHS."/dumps.gz", fopen(self::IMDBRATING_DOWNLOAD_URL, 'r'));
        $this->fileSize = filesize(self::DUMPS_PATHS."/dumps.gz");
        echo "Downloades correctly\n";

        return $this;
    }

    private function decompressFile() : self
    {
        $fileName = self::DUMPS_PATHS."/dumps.gz";
        $buffer_size = 4096; // read 4kb at a time
        $out_file_name = str_replace('.gz', '.tsv', $fileName);
        echo "Uncompressing file...\n";

        $file = gzopen($fileName, 'rb');
        $out_file = fopen($out_file_name, 'wb');

        while (!gzeof($file)) {
            fwrite($out_file, gzread($file, $buffer_size));
        }

        fclose($out_file);
        gzclose($file);
        echo "File uncompressed correctly\n";
        return $this;
    }

    public function importToMongo($collection = 'imdbratings', $filename = 'dumps.tsv'): self
    {
        echo "Importing to mongo...\n";
        $this->dropCollection($collection);
        $db = 'mymoviedb';
        $uploadfile =  self::DUMPS_PATHS."/".$filename;
        $command = "mongoimport --db $db --collection " . $collection . " --file " . $uploadfile ." --type tsv --headerline" ;

        shell_exec($command);

        return $this;
    }

    private function dropCollection(string $collection) : self
    {
        $this->entityManager->getCollection($collection)->drop();

        return $this;
    }
}