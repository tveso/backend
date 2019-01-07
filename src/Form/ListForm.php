<?php
/**
 * Date: 19/12/2018
 * Time: 19:11
 */

namespace App\Form;
use Symfony\Component\Validator\Constraints as Assert;

class ListForm implements \JsonSerializable
{

    /**
     * @Assert\Length(
     *      min = 5,
     *      max = 32,
     * )
     * @Assert\NotNull()
     */
    private $title;
    /**
     * @Assert\Length(
     *      min = 0,
     *      max = 1000,
     * )
     */
    private $description;
    /**
     * @Assert\Count(
     *      min = 0,
     *      max = 100
     * )
     * @Assert\NotNull()
     */
    private $people;
    /**
     * @Assert\Count(
     *      min = 0,
     *      max = 100
     * )
     * @Assert\NotNull()
     */
    private $tvshows;
    /**
     * @Assert\Count(
     *      min = 0,
     *      max = 100
     * )
     * @Assert\NotNull()
     */
    private $movies;
    /**
     * @Assert\Count(
     *      min = 0,
     *      max = 100
     * )
     * @Assert\NotNull()
     */
    private $episodes;


    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }


    /**
     * @return mixed
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * @param mixed $people
     */
    public function setPeople($people): void
    {
        $this->people = $people;
    }

    /**
     * @return mixed
     */
    public function getTvshows()
    {
        return $this->tvshows;
    }

    /**
     * @param mixed $tvshows
     */
    public function setTvshows($tvshows): void
    {
        $this->tvshows = $tvshows;
    }

    /**
     * @return mixed
     */
    public function getMovies()
    {
        return $this->movies;
    }

    /**
     * @param mixed $movies
     */
    public function setMovies($movies): void
    {
        $this->movies = $movies;
    }

    /**
     * @return mixed
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }

    /**
     * @param mixed $episodes
     */
    public function setEpisodes($episodes): void
    {
        $this->episodes = $episodes;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}