<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Document;
use App\Entity\Utilisateur;
use App\Entity\Autorisation;
use App\Entity\Acces;
use App\Entity\Genre;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use DateTime;

class GedController extends AbstractController
{
    /**
     * @Route("/uploadGed", name="uploadGed")
     */
    public function uploadGed(Request $request, EntityManagerInterface $manager): Response
    {
        //Requête pour récupérer toute la table genre
        $listeGenre = $manager->getRepository(Genre::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
        'controller_name' => "Upload d'un Document",
        'listeGenre' => $listeGenre,
        'listeUsers' => $manager->getRepository(Utilisateur::class)->findAll(),
        'listeAutorisation' => $manager->getRepository(Autorisation::class)->findAll(),
        ]);
    }

     /**
     * @Route("/insertGed", name="insertGed")
     */
    public function insertGed(Request $request, EntityManagerInterface $manager): Response
    {
        $sess = $request->getSession();
        if($sess->get("idUtilisateur")){
        //création d'un nouveau document
        $Document = new Document();
        //Récupération et transfert du fichier
        //dd($request->request->get('choix'));
        $brochureFile = $request->files->get("fichier");
        if ($brochureFile){
        $newFilename = uniqid('', true) . "." . $brochureFile->getClientOriginalExtension();
        $brochureFile->move($this->getParameter('upload'), $newFilename);
        //insertion du document dans la base.
        if($request->request->get('choix') == "on"){
        $actif=1;
        }else{
        $actif=2;
        }
        $Document->setActif($actif);
        $Document->setNom($request->request->get('nom'));
        $Document->setTypeId($manager->getRepository(Genre::class)->findOneById($request->request->get('genre')));
        $Document->setCreatedAt(new \Datetime);
        $Document->setChemin($newFilename);

        $manager->persist($Document);
        $manager->flush();
        }
        //Création d'un acces pour la personne avec laquelle on veut partager le document
        if($request->request->get('utilisateur') != -1){
        $user = $manager->getRepository(Utilisateur::class)->findOneById($request->request->get('utilisateur'));
        $autorisation = $manager->getRepository(Autorisation::class)->findOneById($request->request->get('autorisation'));
        $acces = new Acces();
        $acces->setUtilisateurId($user);
        $acces->setAutorisationId($autorisation);
        $acces->setDocumentId($Document);
        $manager->persist($acces);
        $manager->flush();
        }
        //Création d'un accès pour l'uploadeur (propriétaire)
        $user = $manager->getRepository(Utilisateur::class)->findOneById($sess->get("idUtilisateur"));
        $autorisation = $manager->getRepository(Autorisation::class)->findOneById(1);
        $acces = new Acces();
        $acces->setUtilisateurId($user);
        $acces->setAutorisationId($autorisation);
        $acces->setDocumentId($Document);
        $manager->persist($acces);
        $manager->flush();


        return $this->redirectToRoute('listeGed');
        }else{
        return $this->redirectToRoute('authentification');
        }
    }

    /**
     * @Route("/listeGed", name="listeGed")
     */
	public function listeGed(Request $request, EntityManagerInterface $manager): Response
    {
        $sess = $request->getSession();
        if($sess->get("idUtilisateur")){
        //Requête qui récupère la liste des Users
        $listeGed = $manager->getRepository(Acces::class)->findByUtilisateurId($sess->get("idUtilisateur"));
        
        return $this->render('ged/listeGed.html.twig', [
        'controller_name' => "Liste des Documents",
        'listeGed' => $listeGed,
        'listeUsers' => $manager->getRepository(Utilisateur::class)->findAll(),
        'listeAutorisations' => $manager->getRepository(Autorisation::class)->findAll(),
        ]);
        }else{
        return $this->redirectToRoute('authentification');
        }
    }

    /**
     * @Route("/deleteGed/{id}", name="deleteGed")
     */
    public function deleteGed(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
        $sess = $request->getSession();
        if($sess->get("idUtilisateur")){
            //il faut supprimer le lien dans acces
            $recupListeAcces = $manager->getRepository(Acces::class)->findByDocumentId($id);
            foreach($recupListeAcces as $doc){
            $manager->remove($doc);
            $manager->flush();
            }
            //supprimer le fichier du disuqe dur
            //suppression physique du document :
            if(unlink("upload/".$id->getChemin())){
                //suppression du lien dans la base de données
                $manager->remove($id);
                $manager->flush();
            }
            return $this->redirectToRoute('listeGed');
        }else{
        return $this->redirectToRoute('authentification');
        }
    }

     /**
     * @Route("partageGed", name="partageGed")
     */
    public function partageGed(Request $request, EntityManagerInterface $manager): Response
    {
    $sess = $request->getSession();
    if($sess->get("idUtilisateur")){
        //Requête le user en focntion du formulaire
        $user = $manager->getRepository(Utilisateur::class)->findOneById($request->request->get('utilisateur'));
        $autorisation = $manager->getRepository(Autorisation::class)->findOneById($request->request->get('autorisation'));
        $document = $manager->getRepository(Document::class)->findOneById($request->request->get('doc'));
        $acces = new Acces();
        $acces->setUtilisateurId($user);
        $acces->setAutorisationId($autorisation);
        $acces->setDocumentId($document);
        $manager->persist($acces);
        $manager->flush();

        return $this->redirectToRoute('listeGed');
    }else{
    return $this->redirectToRoute('authentification');
    }
    }
}
