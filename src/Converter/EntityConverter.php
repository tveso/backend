<?php
/**
 * Date: 19/09/2018
 * Time: 3:45
 */

namespace App\Converter;


use App\Entity\Entity;
use App\Entity\Show;
use App\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class EntityConverter implements ParamConverterInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request $request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        try{
            $id = $request->attributes->get($configuration->getName());
            $data = $this->entityManager->findOnebyId($id, 'movies');
            $class = $configuration->getClass();
            $entity = new $class($data->getArrayCopy());
            $request->attributes->set('id', $entity);
            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getConverter() === "entityConverter" || $configuration->getClass() instanceof Entity;
    }
}