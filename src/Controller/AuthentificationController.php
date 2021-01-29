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
     * @Route("/insertUserBDD", name="insertUserBDD")
     */
	public function insertUserBdd(Request $request, EntityManagerInterface $manager): Response
	{
			$User = new Utilisateur();
$			User->setNom($request->request->get('nom'));
$			User->setPrenom($request->request->get('prenom'));
$			User->setCode($request->request->get('code'));
$			User->setSalt($request->request->get('salt'));

$			manager->persist($User);
$			manager->flush();
			
		return $this->render('authentification/insertUser.html.twig', [
			'controller_name' => "Ajout en base de données.",
		]);
	}
}
