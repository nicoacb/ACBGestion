<?php

namespace Aviron\SortieBundle\Controller;

use Aviron\SortieBundle\Entity\Bateau;
use Aviron\SortieBundle\Form\BateauType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BateauController extends Controller
{
    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function indexAction()
    {
        $listeBateaux = $this->getDoctrine()
    	->getManager()
    	->getRepository('AvironSortieBundle:Bateau')
        ->getListeBateaux();

        return $this->render('AvironSortieBundle:Bateau:index.html.twig', array('listeBateaux' => $listeBateaux));
    }
    
    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function modifierAction(Request $request, $id)
    {
        // On récupère l'entité du bateau correspondant à l'id $id
        $bateau = $this->getBateauById($id);

        // On génère le formulaire
        $form = $this->createForm(BateauType::class, $bateau);

        // Si la requête est en POST, c'est qu'on veut enregistrer le bateau
        if($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            $form->handleRequest($request);

            // On vérifie que les valeurs entrées sont correctes
            if($form->isValid()) {
                // On enregistre l'objet $bateau en base de données
                $this->persistBateau($bateau);

                // On affiche un message de validation
                $request->getSession()->getFlashBag()->add('notice', 'Bateau bien enregistré.');

                // On redirige vers le cahier de sortie
                return $this->redirectToRoute('aviron_bateaux_home');
            }
        }
        return $this->render('AvironSortieBundle:Bateau:modifier.html.twig',
                                array('form' => $form->createView(), 'bateau' => $bateau));
    }
    
    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function ajouterAction(Request $request)
    {
        // On créé un objet Bateau
        $bateau = new Bateau();

        // On génère le formulaire
        $form = $this->createForm(BateauType::class, $bateau);

        // Si la requête est en POST, c'est qu'on veut enregistrer la sortie
        if($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            $form->handleRequest($request);

            // On vérifie que les valeurs entrées sont correctes
            if($form->isValid()) {
                // On enregistre l'objet $bateau en base de données
                $this->persistBateau($bateau);

                // On affiche un message de validation
                $request->getSession()->getFlashBag()->add('success', 'Bateau bien enregistré.');

                // On redirige vers la liste des bateaux
                return $this->redirectToRoute('aviron_bateaux_home');
            }
        }

        return $this->render('AvironSortieBundle:Bateau:ajouter.html.twig', array('form' => $form->createView()));
    }
    
    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function supprimerAction(Request $request, $id)
    {
        // On récupère l'entité du bateau correspondant à l'id $id
        $bateau = $this->getBateauById($id);

        $bateau->setDatesupp(new \DateTime("now"));

        // On enregistre l'objet $bateau en base de données
        $this->persistBateau($bateau);

        // On affiche un message de validation
        $request->getSession()->getFlashBag()->add('success', 'Bateau bien supprimé.');

        // On redirige vers la liste des bateaux
        return $this->redirectToRoute('aviron_bateaux_home');
    }

    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function mettrehorsserviceAction(Request $request, $id)
    {
        // On récupère l'entité du bateau correspondant à l'id $id
        $bateau = $this->getBateauById($id);

        $bateau->setDatehorsservice(new \DateTime("now"));

        // On enregistre l'objet $bateau en base de données
        $this->persistBateau($bateau);

        // On affiche un message de validation
        $request->getSession()->getFlashBag()->add('success', 'Bateau mis hors-service.');

        // On redirige vers la liste des bateaux
        return $this->redirectToRoute('aviron_bateaux_home');
    }

    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function remettreenserviceAction(Request $request, $id)
    {
        // On récupère l'entité du bateau correspondant à l'id $id
        $bateau = $this->getBateauById($id);

        $bateau->setDatehorsservice(null);

        // On enregistre l'objet $bateau en base de données
        $this->persistBateau($bateau);

        // On affiche un message de validation
        $request->getSession()->getFlashBag()->add('success', 'Bateau mis hors-service.');

        // On redirige vers la liste des bateaux
        return $this->redirectToRoute('aviron_bateaux_home');
    }

    private function getBateauById($id)
    {
         // On récupère l'entité du bateau correspondant à l'id $id
         $bateau = $this->getDoctrine()
         ->getManager()
         ->getRepository('AvironSortieBundle:Bateau')
         ->find($id);

        // Si $bateau est null, l'id n'existe pas
        if(null == $bateau) {
            throw new NotFoundHttpException("Le bateau d'id ".$id." n'existe pas.");
        }

        return $bateau;
    }

    private function persistBateau($bateau)
    {
        // On enregistre l'objet $bateau en base de données
        $em = $this->GetDoctrine()->getManager();
        $em->persist($bateau);
        $em->flush();
    }
}
