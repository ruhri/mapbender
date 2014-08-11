<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\ClassPropertiesParser;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Description of ExchangeDenormalizer
 *
 * @author paul
 */
class ExchangeDenormalizer implements DenormalizerInterface
{

    protected $em;
    protected $mapper;
    protected $current;

    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em an entity manager
     * @param array $mapper mapper old id <-> new id (object)
     */
    public function __construct(EntityManager $em, array $mapper)
    {
        $this->em = $em;
        $this->mapper = $mapper;
    }
    
    public function setCurrentMapper($name)
    {
        $this->current = $name;
    }
    
    private function addClassIntoMapper($class, $idName, $current = null){
        if(!isset($this->mapper[$current ? : $this->current][$class])){
            $this->mapper[$current ? : $this->current][$class] = array(
                'primary' => $idName,
                'map' => array()
            );
        }
    }

    private function addIntoMapper($class, $oldId, $newId, $current = null){
        $this->mapper[$current ? : $this->current][$class]['map'][$oldId] = $newId;
    }
    
    private function getOldId($class, $newId, $current = null){
        foreach($this->mapper[$current ? : $this->current][$class]['map'] as $oldId_ => $newId_){
            if($newId === $newId_){
                return $oldId_;
            }
        }
        return null;
    }
    
    private function getNewId($class, $oldId, $current = null){
        if(isset($this->mapper[$current ? : $this->current][$class]['map'][$oldId])){
            return $this->mapper[$current ? : $this->current][$class]['map'][$oldId];
        } else {
            return null;
        }
    }
    
    public function mapSource($data, $class, $objectExists){
        $reflectionClass = new \ReflectionClass($class);
        $constructorArguments = $data['__class__'][1] ? : array();
        $object = $reflectionClass->newInstanceArgs($constructorArguments);
        $fields = ClassPropertiesParser::parseFields(get_class($object));
        $idName = $this->findIdName($fields);
        if($idName){
            $this->addClassIntoMapper($class, $idName);
        }
        foreach ($fields as $fieldName => $filedProps) {
            if (!isset($filedProps['setter'])) {
                continue;
            }
            $a = 0;
        }
    }
    

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructorArguments = $data['__class__'][1] ? : array();
        $object = $reflectionClass->newInstanceArgs($constructorArguments);
        $fields = ClassPropertiesParser::parseFields(get_class($object));
        $idName = $this->findIdName($fields);
        if($idName){
            $this->addClassIntoMapper($class, $idName);
        }
        foreach ($fields as $fieldName => $filedProps) {
            if (!isset($filedProps['setter'])) {
                continue;
            }
            $a = 0;
        }
        $a = 0;
    }

    private function findIdName($fields)
    {
        foreach ($fields as $fieldName => $filedProps) {
            if(isset($filedProps['Id'])){
                return $fieldName;
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeI($data, $class, $format = null, array $context = array())
    {
//        if(!$this->supportsDenormalization($object, $format)){
//            throw new \Exception("Object not supported for normalization");
//        }

        $reflectionClass = new \ReflectionClass($class);
        $constructorArguments = $data['__class__'][1] ? : array();
        $object = $reflectionClass->newInstanceArgs($constructorArguments);

        foreach ($data as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($object, $setter)) {
                $method = new \ReflectionMethod($class, $setter);
                $args = $method->getParameters();
                $arg = $args[0];
                # to delete start 
                $prs = array(
                    'allowsNull' => $arg->allowsNull(),
                    'canBePassedByValue' => $arg->canBePassedByValue(),
//                'getDefaultValue' => $arg->getDefaultValue(),
                    'getPosition' => $arg->getPosition(),
                    'isArray' => $arg->isArray(),
                    'isCallable' => $arg->isCallable(),
                    'getClass' => !$arg->isArray() ? $arg->getClass() : '',
                    'getClass()->name' => !$arg->isArray() && $arg->getClass() ? $arg->getClass()->name : null,
                    'isOptional' => $arg->isOptional(),
                    'isPassedByReference' => $arg->isPassedByReference(),
                );
                # to delete end 
                if ($arg->isArray()) {
                    if ($value !== null && is_array($value)) {
                        $object->$setter($value);
                    }
                } elseif ($arg->getClass()) {
                    if (is_integer($value)) {#id
                        $object->$setter($value);
                    } elseif ($arg->getClass()->name === "Doctrine\Common\Collections\ArrayCollection") {
                        if ($value !== null && is_array($value)) {
                            $ac = new ArrayCollection();
                            foreach ($value as $item) {
                                $collObj = $this->denormalize($item, $item['__class__'][0]);
                                $ac->add($ac);
                            }
                            $object->$setter($ac);
                        }
                    } else {
                        $object->$setter($value);
                    }
                } else {
                    $object->$setter($value);
                }
            }
        }
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeII($data, $class, $format = null, array $context = array())
    {
//        if(!$this->supportsDenormalization($object, $format)){
//            throw new \Exception("Object not supported for normalization");
//        }

        $reflectionClass = new \ReflectionClass($class);
        $constructorArguments = $data['__class__'][1] ? : array();
        $object = $reflectionClass->newInstanceArgs($constructorArguments);

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $property = $this->findPropertyName($reflectionMethod, array());
            if ($property === null) {
                continue;
            }
            $args = $reflectionMethod->getParameters();
            $arg = $args[0];
            $prs = array(
                'allowsNull' => $arg->allowsNull(),
                'canBePassedByValue' => $arg->canBePassedByValue(),
//                'getDefaultValue' => $arg->getDefaultValue(),
                'getPosition' => $arg->getPosition(),
                'isArray' => $arg->isArray(),
                'isCallable' => $arg->isCallable(),
                'getClass' => !$arg->isArray() ? $arg->getClass() : '',
                'getClass()->name' => !$arg->isArray() && $arg->getClass() ? $arg->getClass()->name : null,
                'isOptional' => $arg->isOptional(),
                'isPassedByReference' => $arg->isPassedByReference(),
            );
            if ($arg->isArray()) {
                if ($data[$property] !== null && is_array($data[$property])) {
                    $reflectionMethod->invoke($class, $data[$property]);
                }
            } elseif ($arg->getClass()) {
                if ($arg->getClass()->name === "Doctrine\Common\Collections\ArrayCollection") {
                    if ($data[$property] !== null && is_array($data[$property])) {
                        $ac = new ArrayCollection($data[$property]);
                        $reflectionMethod->invoke($class, $ac);
                    }
                } else {
                    $reflectionMethod->invoke($class, $data[$property]);
                }
            } else {
                $reflectionMethod->invoke($class, $data[$property]);
            }
//            if ($data[$property] === null) {
//                if ($arg->allowsNull()) {
//                    $reflectionMethod->invoke($class, $data[$property]);
//                }
//            } else {
////                $arg->
//            }
//            return 
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
//        return is_object($data) && $this->supports(get_class($data));
        return $this->supports(get_class($type));
    }

    /**
     * Finds a property name from a "set" function.
     * 
     * @param string $method A method name
     * @param array $properties object properties.
     * @return string a property name or null
     */
    private function findPropertyName($method, $properties = array())
    {
        $methodName = $method->getName();
        if ($method->getNumberOfRequiredParameters() !== 1) {
            return null;
        } else if (strpos(strtolower($methodName), 'set') === 0) {
            return lcfirst(substr($methodName, 3));
        } else {
            return null;
        }
    }

}