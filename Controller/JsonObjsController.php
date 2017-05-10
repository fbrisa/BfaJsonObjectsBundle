<?php

namespace Bfa\JsonObjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/jsonobjs")
 */
class JsonObjsController extends Controller {

	/**
	 * @Route("/", name="jsonobjsform"))
	 */
	public function indexAction(\Symfony\Component\HttpFoundation\Request $request) {
		$em = $this->getDoctrine()->getManager();

		$defaultData = array(
			//'completati' => $completati==1,
			'entityname' => "AppBundle:Esempio",
			'id' => "1",
			'deep' => "0",
		);

		$entities = array();
		$entitiesNames = array();
		$em = $this->getDoctrine()->getManager();
		$meta = $em->getMetadataFactory()->getAllMetadata();
		foreach ($meta as $m) {
			/* @var $m  \Doctrine\ORM\Mapping\ClassMetadata  */

			$cleanClassName = str_replace('\\Entity', '\:', $m->getName());
			$parts = explode('\\', $cleanClassName);
			$className = implode('', $parts);


			$entitiesNames[] = $className;
		}

		usort($entitiesNames, function($a, $b) {
			return $a > $b;
		});

		foreach ($entitiesNames as $className) {
			$entities[$className] = $className;
		}



		$form = $this->createFormBuilder($defaultData)
				->add('entityname', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
					'required' => true,
					'label' => "Entity name",
					'choices' => $entities,
					'choices_as_values' => true,
					'attr' => array('class' => "selezionaTuttoSuFocus"),
					'invalid_message' => 'Valore non valido',
				))
				->add('id', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
					'required' => true,
					'label' => "Id",
					'attr' => array('class' => "selezionaTuttoSuFocus"),
					'invalid_message' => 'Valore non valido',
				))
				->add('deep', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, array(
					'required' => true,
					'label' => "Deep",
					'attr' => array('class' => "selezionaTuttoSuFocus"),
					'invalid_message' => 'Valore non valido',
				))
				->add('invia', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array(
					'attr' => array('icon' => 'search'),
					'label' => ' cerca'
				))
				->getForm()
		;

		$form->handleRequest($request);
		if ($form->isValid()) {
			$dati = $form->getData();

			return $this->redirectToRoute('jsonLoad', array(
						//'redirected' => 1,
						'entityname' => $dati["entityname"],
						'id' => $dati["id"],
						'deep' => $dati["deep"],
			));
//            return $this->redirectToRoute('elencoProduzione', array(
//                //'redirected' => 1,
//                'completati' => $dati["completati"]?1:0,
//                'anno' => $dati["anno"],
//            ));
//            return $this->forward('AppBundle:Produzione:produzione', array(
//                'redirected' => 1,
//                'completati' => $dati["completati"]?1:0,
//                'anno' => $dati["anno"],
//                )
//            );
		}


		return $this->render('BfaJsonObjectsBundle:Default:jsonobjs.html.twig', array(
					'form' => $form->createView(),
		));
	}

	private function carica($entityname, $from = 1, $to = 1, $deep = 0) {
		$em = $this->getDoctrine()->getManager();

                $meta = $em->getMetadataFactory()->getAllMetadata();
		foreach ($meta as $m) {
			/* @var $m  \Doctrine\ORM\Mapping\ClassMetadata  */

			$cleanClassName = str_replace('\\Entity', '\:', $m->getName());
			$parts = explode('\\', $cleanClassName);
			$className = implode('', $parts);

                        if ($className==$entityname) {
                            $entityname=$m->getName();
                            break;
                        }
		}                
                
		$r = $em->getRepository($entityname);
		$cn = $r->getClassName();
		$cmd = $em->getClassMetadata($cn);

		$identifiers = $cmd->getIdentifier();

		if (count($identifiers) == 1) {
			$query = $em->createQuery(
							"SELECT e FROM $entityname e WHERE e.{$identifiers[0]}>=$from AND e.{$identifiers[0]}<=$to"
					);

			try {
				$obj = $query->getResult();

				$jsonHelper = $this->get('bfa.helper.JsonHelper');
                                /* @var $jsonHelper \Bfa\JsonObjectsBundle\Helper\JsonHelper */
				$res = $jsonHelper->toArray($obj, $deep);
				//$res=$jsonHelper->toJson($obj);

				return new \Symfony\Component\HttpFoundation\JsonResponse($res);
			} catch (\Doctrine\ORM\NoResultException $e) {
				
			}
		}
		return new \Symfony\Component\HttpFoundation\JsonResponse([]);
	}

	/**
	 * @Route("/jsonLoad/{entityname}/{id}/{deep}", name="jsonLoad"))
	 */
	public function jsonLoadAction(\Symfony\Component\HttpFoundation\Request $request, $entityname, $id = -1, $deep = 0) {
		$em = $this->getDoctrine()->getManager();


		if (is_numeric($id)) {
			if ($id >= 0) {
				return $this->carica($entityname, $id, $id, $deep);
			} else {
				// ultimo

				$r = $em->getRepository($entityname);
				$cn = $r->getClassName();
				$cmd = $em->getClassMetadata($cn);

				$identifiers = $cmd->getIdentifier();

				if (count($identifiers) == 1) {
					$query = $em->createQuery(
									"SELECT e.{$identifiers[0]} FROM $entityname e ORDER BY e.{$identifiers[0]} DESC"
							)->setMaxResults(-$id);

					try {
						/* @var $query \Doctrine\ORM\Query */
						$objs = $query->getScalarResult();

						if (count($objs) >= (-$id)) {

							$idF = $objs[-$id - 1][$identifiers[0]];

							return $this->carica($entityname, $idF, $idF, $deep);
						}
					} catch (\Doctrine\ORM\NoResultException $e) {
						
					}
				}
			}
		} else {
			// range ?

			if (preg_match("/(\d+)-(\d+)/", $id, $matches)) {
				
				$from=$matches[1];
				$to=$matches[2];

				return $this->carica($entityname,$from,$to,$deep);
			}
		}

		return new \Symfony\Component\HttpFoundation\JsonResponse([]);
	}

}
