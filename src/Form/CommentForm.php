<?php
/**
 * Date: 30/09/2018
 * Time: 19:07
 */

namespace App\Form;
use Symfony\Component\Validator\Constraints as Assert;

class CommentForm
{
    private $text;
    private $parent;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=5,max=1500)
     */
    public function getText()
    {
        return $this->text;
    }


    public function setText($text): void
    {
        $this->text = $text;
    }

    /**
     * @Assert\NotBlank()
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }


}