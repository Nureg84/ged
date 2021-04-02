<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Genre;
use App\Entity\Document;

class GenreController extends AbstractController
{
/**
     * @Route("/genre", name="genre")
     */
public function index(): Response
{
return $this->render('genre/index.html.twig', [
'controller_name' => 'GenreController',
]);
}
/**
     * @Route("/insertGenre", name="insertGenre")
     */
public function insertGenre(): Response
{
return $this->render('genre/insertGenre.html.twig', [
'controller_name' => "Formulaire de création d'un genre",
]);
}
/**
     * @Route("/insertGenreBdd", name="insertGenreBdd")
     */
public function insertGenreBdd(Request $request, EntityManagerInterface $manager): Response
{
$Genre = new Genre();
$Genre->setType($request->request->get('nom'));
$manager->persist($Genre);
$manager->flush();


return $this->render('genre/insertGenre.html.twig', [
'controller_name' => "Ajout en base de données.",
]);
}

/**
     * @Route("/listeGenre", name="listeGenre")
     */
    public function listeGenre(Request $request, EntityManagerInterface $manager): Response
    {
         //Requête qui récupère la liste des genre
              $listeGenre = $manager->getRepository(Genre::class)->findAll();
              
         return $this->render('genre/listeGenre.html.twig', [
     'controller_name' => "Genre des fichiers",
     'listeGenre' => $listeGenre,
         ]);
    }

    /**
     * @Route("/deleteGenre/{id}", name="deleteGenre")
     */
     public function deleteGenre(Request $request, EntityManagerInterface $manager, Genre $id): Response
     {
          //Vérifier que le genre n'est pas utilisé.
          $testGenre = $manager->getRepository(Document::class)->findByTypeId($id->getId());
          if($testGenre){
          $this->addFlash(
          'notice',
          'Ce genre ne peut pas être supprimé'
          );
          }else{
          $manager->remove($id);
          $manager->flush();
          }

          return $this->redirectToRoute('listeGenre');
     }

      /**
     * @Route("/modifGenre/{id}", name="modifGenre")
     */
    public function modifGenre(Request $request, EntityManagerInterface $manager, Genre $id): Response
    {
     $sess = $request->getSession();
     //Information de session
     $sess->set("idGenre", $id);
     
     return $this->render('genre/modifGenre.html.twig', [
          'controller_name' => "Modification d'un Genre",
          'genre' => $id,
     ]);
        
    }

       /**
     * @Route("/modifGenreBdd", name="modifGenreBdd")
     */
    public function modifGenreBdd(Request $request, EntityManagerInterface $manager): Response
    {
     $sess = $request->getSession();
     if($sess->get("idUtilisateur")){
     $genre = $manager->getRepository(Genre::class)->findOneById($sess->get("idGenre")->getId());
     $genre->setType($request->request->get('nom'));
     $manager->persist($genre);
     $manager->flush();

     return $this->redirectToRoute('listeGenre');

     }else{
     return $this->redirectToRoute('authentification');
     }
    }
}