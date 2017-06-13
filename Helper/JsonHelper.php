<?php

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

namespace Bfa\JsonObjectsBundle\Helper;

/**
 * Description of JsonHelper
 *
 * @author francesco
 */
class JsonHelper {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }

    private static function _getGetMethod($oggetto, $reflectionObject, $key) {

        $method = sprintf('get%s', ucwords($key));
        if ($reflectionObject->hasMethod($method)) {
            return $method;
        } else {
            // cerco senza _
            $key = str_replace('_', '', $key);
            $method = sprintf('get%s', ucwords($key));

            if ($reflectionObject->hasMethod($method)) {
                return $method;
            } else {
                // senza ucwords
                $method = sprintf('get%s', $key);
                if ($reflectionObject->hasMethod($method)) {
                    return $method;
                }
            }
        }

        return null;
    }

	/**
	 * 
	 * @param type $oggetto
	 * @param type $em
	 * @return \Doctrine\ORM\Mapping\ClassMetadata
	 */
	public static function getClassMetaData($oggetto, $em) {
		$r = $em->getRepository(get_class($oggetto));
        $cn = $r->getClassName();
        return $em->getClassMetadata($cn); 
        //$an=$cmd->getAssociationNames();
		
	}
	
    private static function toArrayFn_($oggetto, $em, $deep, $arrivoDa = null) {
        $cmd = self::getClassMetaData($oggetto,$em);
		/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
        
		//$an=$cmd->getAssociationNames();
        $fn = $cmd->getFieldNames();
        $associationMappings = $cmd->getAssociationMappings();

        $identifier = $cmd->getIdentifier();
        $nomeColonnaId = $identifier[0];

        $reflectionObject = new \ReflectionObject($oggetto);

        $a = array();

        foreach ($fn as $key) { {

                $type = $cmd->getTypeOfField($key);

                $method = JsonHelper::_getGetMethod($oggetto, $reflectionObject, $key);
                if ($method) {
                    $a[$key] = $oggetto->$method();

                    if ($a[$key] != null) {
                        if ($type == "datetime" || $type == "date") {
                            $a[$key] = $a[$key]->format('c');
                        }
                    }
                } else {
                    // metodo non trovato !
                }


                //$a[$value]=$this->fixApostrofe($this->{$value});
            }
        }

        if ($deep > 0) {
            foreach ($associationMappings as $ass) {
                $key = $ass['fieldName'];
                $method = JsonHelper::_getGetMethod($oggetto, $reflectionObject, $key);
                if ($method) {
                    $oggetto_ = $oggetto->$method();
                    if ($oggetto_) {
                        if ($oggetto_ != $arrivoDa) {
                            if ($ass['type'] == 1) {
                                // ENTITY
                                $a[$key] = JsonHelper::toArrayFn_($oggetto_, $em, $deep - 1, $oggetto);
                            }
                            if ($ass['type'] == 2) {
                                // ENTITY
                                $a[$key] = JsonHelper::toArrayFn_($oggetto_, $em, $deep - 1, $oggetto);
                            }
                            if ($ass['type'] == 4) {
                                // ARRAY
                                $a[$key] = array();
                                foreach ($oggetto_ as $o_) {
                                    $a[$key][] = JsonHelper::toArrayFn_($o_, $em, $deep - 1, $oggetto);
                                }
                            }
                        } else {
                            
                        }
                    } else {
                        $a[$key] = null;
                    }
                }
            }
        }

        return $a;
    }

    private static function toArrayFn($oggetto, $em, $deep) {
        if (is_array($oggetto) || $oggetto instanceof \Doctrine\ORM\PersistentCollection || $oggetto instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $res = array();
            foreach ($oggetto as $obj) {
                $res[] = JsonHelper::toArrayFn_($obj, $em, $deep);
            }
            
            return $res;
        } else {
            return JsonHelper::toArrayFn_($oggetto, $em, $deep);
        }
    }

    public static function toArrayEM($oggetto, \Doctrine\ORM\EntityManager $em, $deep = 0) {
        return self::toArrayFn($oggetto, $em, $deep);
    }

    public function toArray($oggetto, $deep = 0) {
        if ($this->em) {
            return $this->toArrayEM($oggetto, $this->em, $deep);
        } else {
            return "this->em non ancora caricato in " . __FILE__;
        }
    }

    public static function fromArray($oggetto, $arrayConDati, \Doctrine\ORM\EntityManager $em,$cmd=null,$nomeColonnaId=null) {
        $accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

        if ($cmd==null) {            
            $r = $em->getRepository(get_class($oggetto));
            $cn = $r->getClassName();
            $cmd = $em->getClassMetadata($cn); /* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
        }


        $reflectionObject = new \ReflectionObject($oggetto);

        if ($nomeColonnaId==null) {
            $identifier = $cmd->getIdentifierFieldNames();
            $nomeColonnaId = $identifier[0];
        }


        //$associationMappings = $cmd->getAssociationMappings();

        foreach ($arrayConDati as $key => $value) {

            $nomeCampo = $key;
            if (!$reflectionObject->hasProperty($nomeCampo)) {
                // provo a vedere se esiste nei nomi dei campi del database
                if (array_key_exists($key, $cmd->fieldNames)) {
                    $nomeCampo = $cmd->fieldNames[$key];
                } else {
                    // vuol dire che il campo su DB si chiama $key ma nella entity ha il nome...
                    foreach ($cmd->associationMappings as $nomeCampoEntity => $datiArray) {
                        if (isSet($datiArray['joinColumnFieldNames'])) {
                            if (isSet($datiArray['joinColumnFieldNames'][$nomeCampo])) {
                                $nomeCampo = $nomeCampoEntity;
                                break;
                            }
                        }
                    }
                }
            }

            if ($reflectionObject->hasProperty($nomeCampo)) {
                $type = $cmd->getTypeOfField($nomeCampo);

                $nullable = false;
                if ($type != null) {
                    $nullable = $cmd->isNullable($nomeCampo);
                }

                if (array_key_exists($nomeCampo, $cmd->associationMappings)) {
                    // mappato, quindi carico la corrispondente entity

                    if (!is_array($value)) {
                        // va lasciato in quanto $value potrebbe essere un array che deve poi quindi essere gestito appropriatamente.. TO DO
                        
                        $assmap = $cmd->associationMappings[$nomeCampo];

                        $r = $em->getRepository($assmap["targetEntity"]);
                        //$cn=$r->getClassName();
                        $objInstance = $r->find($value);

                        $method = sprintf('set%s', ucwords($nomeCampo));
                        //$oggetto->{$key}=$value;                
                        if ($reflectionObject->hasMethod($method)) {
                            $oggetto->$method($objInstance);
                        } else {
                            $non = "non trovo metodo:" + $method;
                        }
                    } else {
                        // $value e' quindi un array, che faccio ?
                        
                    }
                } else {

                    // vediamo se e' un datetime

                    if ($nomeCampo != $nomeColonnaId) {
                        if ($type == "datetime") {

                            if ($value == null || $value == "") {
                                if ($nullable) {
                                    $value = null;
                                } else {
                                    $value = new \DateTime($value);
                                }
                            } else {
                                $value = new \DateTime($value);
                            }
                        }
                        if ($type == "date") {

                            if ($value == null || $value == "") {
                                if ($nullable) {
                                    $value = null;
                                } else {
                                    $value = new \DateTime($value);
                                }
                            } else {
                                $value = new \DateTime($value);
                            }
                        }
                        $accessor->setValue($oggetto, $nomeCampo, $value);
                    }
                }
            }
        }
    }

//    public static function toArray($object) {
//        $normalizer = new \Symfony\Component\Serializer\Normalizer\ObjectNormalizer();
//        $normalizer->setCircularReferenceHandler(function ($object) {
//            return $object->getId();
//        });
//        
//        $serializer = new \Symfony\Component\Serializer\Serializer(array($normalizer));
//        
//        return $serializer->normalize($object);        
//    }
//    
//    public static function toJson($object) {
//        $encoders = array(new \Symfony\Component\Serializer\Encoder\XmlEncoder(), new \Symfony\Component\Serializer\Encoder\JsonEncoder());
//        $normalizer = new \Symfony\Component\Serializer\Normalizer\ObjectNormalizer();
//        $normalizer->setCircularReferenceHandler(function ($object) {
//            return $object->getId();
//        });
//        $normalizers = array($normalizer);
//        
//        $serializer = new \Symfony\Component\Serializer\Serializer($normalizers, $encoders);
//        $jsonContent = $serializer->serialize($object, 'json');
//        return $jsonContent;
//    }
//    
//    public static function fromArray($arrayConDati) {
//    }    
}
