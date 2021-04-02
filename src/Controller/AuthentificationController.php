<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Autorisation;
use App\Entity\Utilisateur;
use App\Entity\Acces;

class AuthentificationController extends AbstractController
{
    /**
     * @Route("/authentification", name="authentification")
     */
    public function index(): Response
    {
        return $this->render('authentification/index.html.twig', [
            'controller_name' => 'AuthentificationController',
        ]);
    }
	
	 /**
     * @Route("/insertUser", name="insertUser")
     */
	public function insertUser(Request $request): Response
	{
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			return $this->render('authentification/insertUser.html.twig', [
				'controller_name' => "Insertion d'un nouvel Utilisateur",
			]);
		}else{
			return $this->redirectToRoute('authentification');
		}
	}
	
	/**
     * @Route("/insertUserBdd", name="insertUserBDD")
     */
	public function insertUserBdd(Request $request, EntityManagerInterface $manager): Response
	{
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			$User = new Utilisateur();
			$User->setNom($request->request->get('nom'));
			$User->setPrenom($request->request->get('prenom'));
			$User->setCode($request->request->get('code'));
			$User->setSalt($request->request->get('salt'));
			
			$manager->persist($User);
			$manager->flush();


			return $this->render('authentification/insertUser.html.twig', [
				'controller_name' => "Ajout en base de données.",
			]);
		}else{
			return $this->redirectToRoute('authentification');
		}
	}
	
	/**
     * @Route("/listeUser", name="listeUser")
     */
	public function listeUser(Request $request, EntityManagerInterface $manager): Response
	{
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			//Requête qui récupère la liste des Users
			$listeUser = $manager->getRepository(Utilisateur::class)->findAll();

			return $this->render('authentification/listeUser.html.twig', [
				'controller_name' => "Liste des Utilisateurs",
				'listeUser' => $listeUser,
			]);
		}else{
			return $this->redirectToRoute('authentification');
		}
	}
	
	/**
     * @Route("/connexion", name="connexion")
     */
	public function connexion(Request $request, EntityManagerInterface $manager): Response
		{
			//Récupération des identifiants de connexion
			$identifiant = $request->request->get('login');
			$password = $request->request->get('password');
			//Test de l'existence d'un tel couple
			$aUser = $manager->getRepository(Utilisateur::class)->findBy(["nom"=>$identifiant, "code"=>$password]);
			if (sizeof($aUser)>0){
				$utilisateur = new Utilisateur;
				$utilisateur = $aUser[0];
				//démarrage des variables de session
				$sess = $request->getSession();
				//Information de session
				$sess->set("idUtilisateur", $utilisateur->getId());
				$sess->set("nomUtilisateur", $utilisateur->getNom());
				$sess->set("prenomUtilisateur", $utilisateur->getPrenom());

				return $this->redirectToRoute('dashboard');	
			}else{
				return $this->redirectToRoute('authentification');
			}
			dd($identifiant, $password, $reponse);
			return new response(1);
			// return $this->render('authentification/insertUser.html.twig', [
			// 'controller_name' => "Ajout en base de données.",
			// ]);
		}

		/**
     * @Route("/logout", name="logout")
     */
		public function logout(Request $request, EntityManagerInterface $manager): Response
		{
		$sess = $request->getSession();
		$sess->remove("idUtilisateur");
		$sess->invalidate();
		$sess->clear();
		$sess=$request->getSession()->clear();
		return $this->redirectToRoute('authentification');
		}
		
		/**
     * @Route("/dashboard", name="dashboard")
     */
	public function dashboard(Request $request, EntityManagerInterface $manager): Response
	{
		{
			$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			//*******************Requetes Mysql*******************
			//Récupération du nombre de document
			$listeDocuments = $manager->getRepository(Acces::class)->findByUtilisateurId($sess->get("idUtilisateur"));
			$listeDocumentAll = $manager->getRepository(Acces::class)->findAll(); 
			$listeUsers = $manager->getRepository(Utilisateur::class)->findAll();
			$listeAutorisations = $manager->getRepository(Autorisation::class)->findAll();
			//*********************Variables*********************
			$flag = 0 ; //indique que le document privé
			$nbDocument = 0;
			$nbDocumentPrives = 0;
			$documentPrives = Array();
			$lastDocument = new \Datetime("2000-01-01");
			
			foreach($listeDocuments as $val){
				$nbDocument++;	
				$document = $val->getDocumentId()->getId();
				if($val->getDocumentId()->getCreatedAt() > $lastDocument){
					$lastDocument = $val->getDocumentId()->getCreatedAt();
					$documentDate = $val->getDocumentId();
					$autorisationDocument = $val->getautorisationId();
					
				}
				foreach($listeDocumentAll as $val2){
					if($val2->getDocumentId()->getId() == $document && $val2->getUtilisateurId()->getId() != $sess->get("idUtilisateur") )
						$flag++;	
				}
				if($flag == 0){
					$documentPrives[] = $val ;
					$nbDocumentPrives ++;
				}
				$flag =0;
			}
			return $this->render('authentification/dashboard.html.twig',[
			 'controller_name' => "Espace Client",
			 'nb_document' => $nbDocument,
			 'listeDocumentPrives' => $documentPrives,
			 'nbDocumentPrives' => $nbDocumentPrives,
			 'listeUsers' => $listeUsers,
			 'listeAutorisations' => $listeAutorisations,
			 'documentDate' => $documentDate,
			 'listeGed' => $listeDocumentAll,
			 'autorisation' => $autorisationDocument,
			 ]);
		}else{
			return $this->redirectToRoute('authentification');
		}
			}
	}
	
	/**
	* @Route("/deleteUser/{id}", name="deleteUser")
	*/
	public function deleteUser(Request $request, EntityManagerInterface $manager, Utilisateur $id): Response
	{

		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			$manager->remove($id);
			$manager->flush();
			return $this->redirectToRoute('listeUser');
		}else{
			return $this->redirectToRoute('authentification');
		}
	}
}
