<?php

namespace App\Controller;

use App\Entity\MembreContacts;
use App\Entity\MembreLicences;
use App\Entity\Saison;
use App\Entity\User;
use App\Form\MembreLicenceType;
use App\Form\NumeroLicenceType;
use App\Form\PreinscriptionFlow;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MembreLicencesController extends AbstractController
{
    public function preinscription(PreinscriptionFlow $flow, UserPasswordEncoderInterface $encoder, SessionInterface $session)
    {
        $membre = new User(); // Your form data class. Has to be an object, won't work properly with an array.
        $contactPortable = new MembreContacts();
        $contactPortable->setTypeContact(1);
        $membre->addContact($contactPortable);

        $contactUrgence = new MembreContacts();
        $contactUrgence->setTypeContact(2);
        $membre->addContact($contactUrgence);

        //$flow = $this->get('app.form.flow.preinscription'); // must match the flow's service id
        $flow->bind($membre);

        // form of the current step
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // flow finished
                $motdepasse = $membre->getPassword();
                $newEncodedPassword = $encoder->encodePassword($membre, $membre->getPassword());
                $membre->setPassword($newEncodedPassword);
                $this->EnregistreMembre($membre);                

                $saison = $this->DonneSaison(7);
                $licence = new MembreLicences();
                $licence->setSaison($saison);
                $licence->setMembre($membre);
                $this->EnregistreLicence($licence);

                $session->set('idFicheInscription', $licence->getId());

                $flow->reset(); // remove step data from the session

                return $this->render('membre_licences/preinscriptionenvoyee.html.twig', [
                    'motdepasse' => $motdepasse
                ]);
            }
        }

        return $this->render('membre_licences/preinscription.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
        ]);
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function preinscriptions()
    {
        $preinscriptions = $this->getDoctrine()
            ->getManager()
            ->getRepository(MembreLicences::class)
            ->DonnePreinscriptions();

        $licencesASaisir = $this->getDoctrine()
            ->getManager()
            ->getRepository(MembreLicences::class)
            ->DonneLicencesASaisir();

        return $this->render(
            'membre_licences/preinscriptions.html.twig',
            array(
                'preinscriptions'   => $preinscriptions,
                'licencesasaisir'   => $licencesASaisir
            )
        );
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function validerInscription(Request $request, $id)
    {
        $licence = $this->DonneLicence($id);

        $form = $this->createForm(MembreLicenceType::class, $licence);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $licence->setDateValidationInscription(new \DateTime("now"));
                $licence->setUserValidationInscription($this->getUser());
                $this->EnregistreLicence($licence);

                $this->addFlash('success', 'Inscription validée : licence à saisir.');

                return $this->redirectToRoute('aviron_membre_licences_preinscriptions');
            }
        }

        return $this->render(
            'membre_licences/validerinscription.html.twig',
            array('form' => $form->createView(), 'demande' => $licence)
        );
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function supprimerInscription($id)
    {
        $licence = $this->DonneLicence($id);
        $licence->setDateSuppressionInscription(new \DateTime("now"));
        $licence->setUserSuppressionInscription($this->getUser());
        $this->EnregistreLicence($licence);

        $this->addFlash('success', 'Demande d\'inscription bien supprimée.');

        return $this->redirectToRoute('aviron_membre_licences_preinscriptions');
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function marquerCommeSaisie(Request $request, $id)
    {
        $licence = $this->DonneLicence($id);
        $membre = $licence->getMembre();

        if ($membre->getLicence() == null) {
            $form = $this->createForm(NumeroLicenceType::class, $membre);

            if ($request->isMethod('POST')) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $this->EnregistreMembre($membre);

                    $licence->setDateSaisieLicence(new \DateTime("now"));
                    $licence->setUserSaisieLicence($this->getUser());
                    $this->EnregistreLicence($licence);

                    $this->addFlash('success', 'Licence marquée comme étant saisie.');

                    return $this->redirectToRoute('aviron_membre_licences_preinscriptions');
                }
            }

            return $this->render(
                'membre_licences/validerinscription.html.twig',
                array('form' => $form->createView(), 'demande' => $licence)
            );
        } else {
            $licence->setDateSaisieLicence(new \DateTime("now"));
            $licence->setUserSaisieLicence($this->getUser());
            $this->EnregistreLicence($licence);

            $this->addFlash('success', 'Licence marquée comme étant saisie.');

            return $this->redirectToRoute('aviron_membre_licences_preinscriptions');
        }
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function voirFicheInscription($id)
    {
        return $this->DonneFicheInscription($id);
    }

    public function telechargerFicheInscription(SessionInterface $session)
    {
        $id = $session->get('idFicheInscription');
        return $this->DonneFicheInscription($id);
    }

    private function DonneFicheInscription($id)
    {
        $licence = $this->DonneLicence($id);
        $membre = $licence->getMembre();

        $ficheInscription = new TemplateProcessor('Fiche_inscription_test.docx');
        $ficheInscription->setValue('nom', $membre->getNom());
        $ficheInscription->saveAs('plop.docx');

        $reponse = new BinaryFileResponse('plop.docx');
        $reponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'plop.docx'
        );
        return $reponse;
    }

    private function DonneLicence($id)
    {
        $licence = $this->getDoctrine()
            ->getManager()
            ->getRepository(MembreLicences::class)
            ->find($id);

        if (null == $licence) {
            throw new NotFoundHttpException("La licence d'id " . $id . " n'existe pas.");
        }

        return $licence;
    }

    private function EnregistreLicence($licence)
    {
        $em = $this->GetDoctrine()->getManager();
        $em->persist($licence);
        $em->flush();
    }

    private function DonneSaison($id)
    {
        $saison = $this->getDoctrine()
            ->getManager()
            ->getRepository(Saison::class)
            ->find($id);

        if (null == $saison) {
            throw new NotFoundHttpException("La saison d'id " . $id . " n'existe pas.");
        }

        return $saison;
    }

    private function EnregistreMembre($membre)
    {
        $em = $this->GetDoctrine()->getManager();
        $em->persist($membre);
        $em->flush();
    }
}