<?php
/**
 * Date: 06/10/2018
 * Time: 21:02
 */

namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;

class RateForm
{

    /**
     * @var string
     */
    private $id;
    /**
     * @var float
     */
    private $rating;

    /**
     * @return mixed
     * @Assert\NotBlank()
     * @Assert\Regex("/^tt[0-9]{7,8}/")
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @Assert\Range(
     *      min = 0,
     *      max = 10
     * )
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating): void
    {
        $this->rating = $rating;
    }


}