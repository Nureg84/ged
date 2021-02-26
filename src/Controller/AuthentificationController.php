<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;

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
	public function insertUser(): Response
	{
		return $this->render('authentification/insertUser.html.twig', [
			'controller_name' => "Insertion d'un nouvel Utilisateur",
		]);
	}
	
	/**
     * @Route("/insertUserBdd", name="insertUserBDD")
     */
	public function insertUserBdd(Request $request, EntityManagerInterface $manager): Response
	{
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
	}
	
	/**
     * @Route("/listeUser", name="listeUser")
     */
	public function listeUser(Request $request, EntityManagerInterface $manager): Response
	{
		//Requête qui récupère la liste des users
			$listeUsers = $manager->getRepository(Utilisateur::class)->findAll();
			
		return $this->render('authentification/listeUser.html.twig', [
'controller_name' => "Liste des Utilisateurs",
'listeUser' => $listeUsers,
		]);
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
     * @Route("/dashboard", name="dashboard")
     */
	public function dashboard(Request $request, EntityManagerInterface $manager): Response
	{
		$sess = $request->getSession();
		return $this->render('authentification/dashboard.html.twig',[
			'controller_name' => "Espace Client",
		]);
	}
}
