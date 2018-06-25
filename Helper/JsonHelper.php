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

	private static function startsWith($haystack, $needle)
	{
		 $length = strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}

	private static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);

		return $length === 0 || 
		(substr($haystack, -$length) === $needle);
	}
	
	private static function _getGetMethod($oggetto, \ReflectionObject $reflectionObject, $key) {

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

		// magari e' un boolean....
		$method = sprintf('is%s', ucwords($key));
		if ($reflectionObject->hasMethod($method)) {
			return $method;
		} else {
			// cerco senza _
			$key = str_replace('_', '', $key);
			$method = sprintf('is%s', ucwords($key));

			if ($reflectionObject->hasMethod($method)) {
				return $method;
			} else {
				// senza ucwords
				$method = sprintf('is%s', $key);
				if ($reflectionObject->hasMethod($method)) {
					return $method;
				}
			}
		}

		// magari senza niente di aggiuntivo
		if ($reflectionObject->hasMethod($key)) {
			return $key;
		}

		return null;
	}
	private static function _getAddMethod($oggetto, \ReflectionObject $reflectionObject, $key) {

		$method = sprintf('add%s', ucwords($key));
		if ($reflectionObject->hasMethod($method)) {
			return $method;
		} elseif ( JsonHelper::endsWith ( $key,"s") && strlen($key)>1) {
			$keyWithousS= substr($key, 0, strlen($key)-1);
			$method = sprintf('add%s', ucwords($keyWithousS));
			if ($reflectionObject->hasMethod($method)) {
				return $method;
			}
		} else {
			// cerco senza _
			$key = str_replace('_', '', $key);
			$method = sprintf('add%s', ucwords($key));

			if ($reflectionObject->hasMethod($method)) {
				return $method;
			} else {
				// senza ucwords
				$method = sprintf('add%s', $key);
				if ($reflectionObject->hasMethod($method)) {
					return $method;
				}
			}
		}

	
		

		// magari senza niente di aggiuntivo
		if ($reflectionObject->hasMethod($key)) {
			return $key;
		}

		return null;
	}
	private static function _getRemoveMethod($oggetto, \ReflectionObject $reflectionObject, $key) {

		$method = sprintf('remove%s', ucwords($key));
		if ($reflectionObject->hasMethod($method)) {
			return $method;
		} elseif ( JsonHelper::endsWith ( $key,"s") && strlen($key)>1) {
			$keyWithousS= substr($key, 0, strlen($key)-1);
			$method = sprintf('remove%s', ucwords($keyWithousS));
			if ($reflectionObject->hasMethod($method)) {
				return $method;
			}
		} else {
			// cerco senza _
			$key = str_replace('_', '', $key);
			$method = sprintf('remove%s', ucwords($key));

			if ($reflectionObject->hasMethod($method)) {
				return $method;
			} else {
				// senza ucwords
				$method = sprintf('remove%s', $key);
				if ($reflectionObject->hasMethod($method)) {
					return $method;
				}
			}
		}

	
		

		// magari senza niente di aggiuntivo
		if ($reflectionObject->hasMethod($key)) {
			return $key;
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

	private static function toArrayFn_($oggetto, $em, $deep, $arrivoDa = null, $includeArray = true, $maschera = null, $permetti = null) {
		if ($oggetto == null) {
			return [];
		}
		$cmd = self::getClassMetaData($oggetto, $em);
		/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */

		//$an=$cmd->getAssociationNames();
		$fn = $cmd->getFieldNames();
		$associationMappings = $cmd->getAssociationMappings();

//		  $identifier = $cmd->getIdentifier();
//        $nomeColonnaId = $identifier[0];

		$reflectionObject = new \ReflectionObject($oggetto);

		$a = array();

		if ($permetti) {
			$fn = array_merge($fn, $permetti);
		}

		foreach ($fn as $key) {
			if ($key == "latLng") {
				$key = $key;
			}

			$consensoMaschera = true;
			if ($maschera != null) {
				if ($deep == self::$startDeep) {
					$consensoMaschera = in_array($key, $maschera);
				}
			}


			if ($consensoMaschera) {
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
					$method = $method;
				}
			}


			//$a[$value]=$this->fixApostrofe($this->{$value});
		}

		if ($deep > 0) {
			foreach ($associationMappings as $ass) {
				$key = $ass['fieldName'];
				$method = JsonHelper::_getGetMethod($oggetto, $reflectionObject, $key);
				if ($method) {
					$oggetto_ = $oggetto->$method();
					if ($oggetto_) {
						if ($oggetto_ != $arrivoDa) {

							$consensoPermettiMaschera = false;
							if ($permetti != null) {
								$consensoPermettiMaschera = in_array($key, $permetti);
							}

							$consensoMaschera = true;
							if ($maschera != null) {
								if ($deep == self::$startDeep) {
									$consensoMaschera = in_array($key, $maschera);
								}
							}

							if ($consensoMaschera || $consensoPermettiMaschera) {
								switch ($ass['type']) {
									case 1:
										// ENTITY
										$a[$key] = JsonHelper::toArrayFn_($oggetto_, $em, $deep - 1, $oggetto, $includeArray, $maschera, $permetti);
										break;
									case 2:
										// ENTITY
										$a[$key] = JsonHelper::toArrayFn_($oggetto_, $em, $deep - 1, $oggetto, $includeArray, $maschera, $permetti);
										break;
									case 4:
									case 8:
										// ARRAY
										$a[$key] = array();

										if ($includeArray || $consensoPermettiMaschera) {
											foreach ($oggetto_ as $o_) {
												$a[$key][] = JsonHelper::toArrayFn_($o_, $em, $deep - 1, $oggetto, $includeArray, $maschera, $permetti);
											}
										}
										break;
									default:
										$a = $a;
										break;
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

	private static function toArrayFn($oggetto, $em, $deep, $includeArray = true, $maschera = null, $permetti = null) {
		if (is_array($oggetto) || $oggetto instanceof \Doctrine\ORM\PersistentCollection || $oggetto instanceof \Doctrine\Common\Collections\ArrayCollection) {
			$res = array();
			foreach ($oggetto as $obj) {
				$res[] = JsonHelper::toArrayFn_($obj, $em, $deep, null, $includeArray, $maschera, $permetti);
			}

			return $res;
		} else {
			return JsonHelper::toArrayFn_($oggetto, $em, $deep, null, $includeArray, $maschera, $permetti);
		}
	}

	private static $startDeep = 0;

	public static function toArrayEM($oggetto, \Doctrine\ORM\EntityManager $em, $deep = 0, $includeArray = true, $maschera = null, $permetti = null) {
		self::$startDeep = $deep;
		return self::toArrayFn($oggetto, $em, $deep, $includeArray, $maschera, $permetti);
	}

	public function toArray($oggetto, $deep = 0) {
		if ($this->em) {
			return $this->toArrayEM($oggetto, $this->em, $deep);
		} else {
			return "this->em non ancora caricato in " . __FILE__;
		}
	}

	private static function getIdentifierByRepository(\Doctrine\ORM\EntityRepository $r, \Doctrine\ORM\EntityManager $em) {
		$cnA = $r->getClassName();
		$cmdA = $em->getClassMetadata($cnA);
		/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
		$idents = $cmdA->getIdentifier();

		if (count($idents) > 0) {
			return $idents[0];
		}

		return null;
	}

	private static function caricaOggettoDaStruttura($oggettoArr, \Doctrine\ORM\EntityRepository $r, \Doctrine\ORM\EntityManager $em) {
		$ident = self::getIdentifierByRepository($r, $em);

		if ($ident != null) {
			if (isset($oggettoArr[$ident])) {
				return $r->find($oggettoArr[$ident]);
			}
		}

		return null;
	}

	private static function isEntityIdSameOfArray($oggettoArray, $oggettoEntity, $em) {
		$r = $em->getRepository(get_class($oggettoEntity));

		$ident = self::getIdentifierByRepository($r, $em);

		$cn2 = $r->getClassName();
		$cmd2 = $em->getClassMetadata($cn2);
		/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
		$vidAss = $cmd2->getIdentifierValues($oggettoEntity);
		$vid = $vidAss[$ident];

		if (isSet($oggettoArray[$ident])) {
			if ($vid == $oggettoArray[$ident]) {
				return true;
			}
			
		}

		return false;
	}

	private static function getArrayIndexOfEntity($arrayOfData, $oggettoEntity, $em) {
		for ($i = 0; $i < count($arrayOfData); $i++) {
			if (self::isEntityIdSameOfArray($arrayOfData[$i], $oggettoEntity, $em)) {
				return $i;
			}
		}

		return FALSE;
	}

	private static function getEtityIndexOfArray($arrayOfEntities, $oggettoArray, $em) {
		for ($i = 0; $i < count($arrayOfEntities); $i++) {
			if (self::isEntityIdSameOfArray($oggettoArray, $arrayOfEntities[$i], $em)) {
				return $i;
			}
		}

		return FALSE;
	}

	public static function fromArray(
	$oggetto, $arrayConDati, \Doctrine\ORM\EntityManager $em, $cmd = null, $nomeColonnaId = null
//            ,$includeArray = true
//            , $maschera = null
//            , $permetti = null
	) {
		$accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

		if ($cmd == null) {
			$r = $em->getRepository(get_class($oggetto));
			$cn = $r->getClassName();
			$cmd = $em->getClassMetadata($cn); /* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
		}


		$reflectionObject = new \ReflectionObject($oggetto);

		if ($nomeColonnaId == null) {
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

					$assmap = $cmd->associationMappings[$nomeCampo];

					$r = $em->getRepository($assmap["targetEntity"]);
					$subType = $assmap["type"];
					//$cn=$r->getClassName();

					if (is_array($value)) {


						if (
								$subType == 4
						) {
							// ont to many
							// quali sono da eliminare?
							$method = sprintf('get%s', ucwords($nomeCampo));
							$elencoOra = $oggetto->$method($objInstance);

							foreach ($elencoOra as $entityAttuale) {
								$pos = self::getArrayIndexOfEntity($value, $entityAttuale, $em);
								if ($pos !== FALSE) {
									// esiste
									// aggiorno
									self::fromArray($entityAttuale, $value[$pos], $em);
								} else {
									// non esiste, da togliere
									$method = sprintf('remove%s', ucwords($nomeCampo));
									if ($reflectionObject->hasMethod($method)) {
										$oggetto->$method($entityAttuale);
									} else {
										$non = "non trovo metodo:$method";
									}
								}
							}

							// ora vedo se ce ne e' qualcuno da aggiungere
							$identKey = self::getIdentifierByRepository($r, $em);
							foreach ($value as $oggettoArrAttuale) {

								$daCreare = true;
								if (isset($oggettoArrAttuale[$identKey])) {

									if ($oggettoArrAttuale[$identKey] > 0) {
										$pos = self::getEtityIndexOfArray($elencoOra, $oggettoArrAttuale, $em);
										if ($pos === FALSE) {
											// carico

											$objInstance = self::caricaOggettoDaStruttura($oggettoArrAttuale, $r, $em);
											if (!$objInstance) {
												// cro quindi nuovo
												// id non piu' presente !
//										self::fromArray($oggetto, $oggettoArrAttuale, $em) NON VA
//										$objInstance = self::caricaOggettoDaStruttura($oggettoArrAttuale, $r, $em); NON VA
											} else {
												$method = sprintf('add%s', ucwords($nomeCampo));
												//$oggetto->{$key}=$value;                
												if ($reflectionObject->hasMethod($method)) {
													$oggetto->$method($objInstance);
												} else {
													$non = "non trovo metodo:$method";
												}
											}
										} else {
											$daCreare = false;
										}
									}
								}

								if ($daCreare) {
									$className = $r->getClassName();

									$objInstance = new $className();
									self::fromArray($objInstance, $oggettoArrAttuale, $em);


									$method = sprintf('add%s', ucwords($nomeCampo));
									//$oggetto->{$key}=$value;                
									if ($reflectionObject->hasMethod($method)) {
										$oggetto->$method($objInstance);
									} else {
										$non = "non trovo metodo:$method";
									}
								}
							}

//							foreach ($value as $viter) {
//								$objInstance = self::caricaOggettoDaStruttura($viter, $r, $em);
//
//								$trovato = false;
//								foreach ($elencoOra as $attuale) {
//									$ident = self::getIdentifierByRepository($r, $em);
//
//									$cn2 = $r->getClassName();
//									$cmd2 = $em->getClassMetadata($cn2);
//									/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
//									$vidAss = $cmd2->getIdentifierValues($attuale);
//									$vid = $vidAss[$ident];
//
//									if ($vid == $viter[$ident]) {
//										$trovato = true;
//										break;
//									}									
//								}
//								
//								if (!$trovato) {
//									
//								}
//							}
						}





						if (
								$subType == 8
						) {
							// many to many (8)
							// si tratto di un array, quindi vedo se mettere o togliere
							// array attuale in istanza oggetto
							$method = sprintf('get%s', ucwords($nomeCampo));
							$elencoOra = $oggetto->$method($objInstance);

							foreach ($value as $viter) {
								$objInstance = self::caricaOggettoDaStruttura($viter, $r, $em);

								$trovato = false;
								foreach ($elencoOra as $attuale) {
									$ident = self::getIdentifierByRepository($r, $em);

									$cn2 = $r->getClassName();
									$cmd2 = $em->getClassMetadata($cn2);
									/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
									$vidAss = $cmd2->getIdentifierValues($attuale);
									if (isSet($vidAss[$ident])) {
										$vid = $vidAss[$ident];
										if ($vid == $viter[$ident]) {
											$trovato = true;
											break;
										}
									}
								}

								if (!$trovato) {
									// non trovato, va aggiunto
									$method = JsonHelper::_getAddMethod($objInstance, $reflectionObject, $nomeCampo);
									
//									$method =  sprintf('add%s', ucwords($nomeCampo));
									//$oggetto->{$key}=$value;                
									if ($reflectionObject->hasMethod($method)) {
										$oggetto->$method($objInstance);
									} else {
										$non = "non trovo metodo:$method";
									}
								}
							}
							
							// vedo quali rimuovere
							foreach ($elencoOra as $attuale) {
								$ident = self::getIdentifierByRepository($r, $em);
								
								$trovato=false;
								foreach ($value as $viter) {
									$objInstance = self::caricaOggettoDaStruttura($viter, $r, $em);
									
									
									$cn2 = $r->getClassName();
									$cmd2 = $em->getClassMetadata($cn2);
									/* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
									$vidAss = $cmd2->getIdentifierValues($attuale);
									if (isSet($vidAss[$ident])) {
										$vid = $vidAss[$ident];
										if ($vid == $viter[$ident]) {
											$trovato = true;
											break;
										}
									}
								}
								
								if (! $trovato) {
									// non trovato, va aggiunto
									$method = JsonHelper::_getRemoveMethod($objInstance, $reflectionObject, $nomeCampo);
									
//									$method =  sprintf('add%s', ucwords($nomeCampo));
									//$oggetto->{$key}=$value;                
									if ($reflectionObject->hasMethod($method)) {
										
										
										$oggetto->$method($attuale);
									} else {
										$non = "non trovo metodo:$method";
									}
								}
							}
							
						} else {
							$objInstance = self::caricaOggettoDaStruttura($value, $r, $em);
						}



//                        $cnA = $r->getClassName();
//                        $cmdA = $em->getClassMetadata($cnA); /* @var $cmd \Doctrine\ORM\Mapping\ClassMetadata */
//                        $idents = $cmdA->getIdentifier();
//                        if (count($idents) > 0) {
//
//                            if (isset($value[$idents[0]])) {
//                                $objInstance = $r->find($value[$idents[0]]);
//                            }
//                        }
					} else {
						$objInstance = $r->find($value);
					}


					if ($subType !== 8) {
						/// non e' un array
						$method = sprintf('set%s', ucwords($nomeCampo));
						//$oggetto->{$key}=$value;                
						if ($reflectionObject->hasMethod($method)) {
							$oggetto->$method($objInstance);
						} else {
							$non = "non trovo metodo:$method";
						}
					}
				} else {

					// vediamo se e' un datetime

					if ($nomeCampo != $nomeColonnaId) {

						switch ($type) {
							case "datetime":
								if ($value == null || $value == "") {
									if ($nullable) {
										$value = null;
									} else {
										$value = new \DateTime($value);
									}
								} else {
									$value = new \DateTime($value);
								}
								break;
							case "date":
								if ($value == null || $value == "") {
									if ($nullable) {
										$value = null;
									} else {
										$value = new \DateTime($value);
									}
								} else {
									$value = new \DateTime($value);
								}
								break;
							case "boolean":
								if ($value == null || $value == "") {
									if ($nullable) {
										$value = null;
									} else {
										$value = false;
									}
								} else {
									$low = strtolower($value);
									$value = ($low == 'true' || $low == '1' || $low == 't' || $low == 'y' || $low == 's' || $low == 'yes' || $low == 'si');
								}
								break;
							default:
								$type = $type;
								break;
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