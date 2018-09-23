<?php
/**
 * Date: 19/09/2018
 * Time: 3:45
 */

namespace App\Converter;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class ObjectConverter implements ParamConverterInterface
{

    /**
     * Stores the object in the request.
     *
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     * @throws \Exception
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $class = $configuration->getClass();
        try{
            $object = new $class();
            foreach ($request->request->all() as $key=>$param){
                $method = ucfirst($key);
                $object->{'set'.$method}($param);
            }
            $request->attributes->set('user',$object);

            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    /**
     * Checks if the object is supported.
     *
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {

        return $configuration->getConverter() === "class" and  $configuration->getClass()!== null;
    }
}