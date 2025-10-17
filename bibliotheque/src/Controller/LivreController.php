<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Auteur;
use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LivreController extends AbstractController
{
    #[Route('/livre/add', name: 'add_livre', methods: ['POST'])]
    public function addLivre(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        $auteurId = $content['idAuteur'] ?? null;
        $auteur = $entityManager->getRepository(Auteur::class)->find($auteurId);
        if (!$auteur) {
            return new Response('Auteur non trouvé', 404);
        }

        $categorieId = $content['categorie'] ?? null;
        $categorie = $entityManager->getRepository(Categorie::class)->find($categorieId);
        if (!$categorie) {
            return new Response('Catégorie non trouvée', 404);
        }

        $livre = new Livre();
        $livre->setTitre($content['titre']);
        $livre->setDatePublication(new \DateTime($content['datePublication']));
        $livre->setDisponible($content['disponible']);
        $livre->setIdAuteur($auteur); 
        $livre->setCategorie($categorie);

        $entityManager->persist($livre);
        $entityManager->flush();

        return new Response('Added new livre with id ' . $livre->getId());
    }


    #[Route('/livre/{id}', name: 'get_livre', methods: ['GET'])]
    public function getLivre(int $id, EntityManagerInterface $entityManager): Response
    {
        $livre = $entityManager->getRepository(Livre::class)->find($id);

        if (!$livre) {
            return new Response('Livre non trouvé', 404);
        }

        $data = [
            'id' => $livre->getId(),
            'titre' => $livre->getTitre(),
            'datePublication' => $livre->getDatePublication()->format('Y-m-d'),
            'disponible' => $livre->isDisponible(),
            'idAuteur' => $livre->getIdAuteur()->getId(),
            'categorie' => $livre->getCategorie()->getId(),
        ];

        return $this->json($data);
    }


    #[Route('/livres', name: 'get_all_livres', methods: ['GET'])]
    public function getAllLivres(EntityManagerInterface $entityManager): Response
    {
        $livres = $entityManager->getRepository(Livre::class)->findAll();
        $data = [];
        foreach ($livres as $livre) {
            $data[] = [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'datePublication' => $livre->getDatePublication()->format('Y-m-d'),
                'disponible' => $livre->isDisponible(),
                'idAuteur' => $livre->getIdAuteur()->getId(),
                'categorie' => $livre->getCategorie()->getId(),
            ];
        }
        return $this->json($data);
    }

    
    #[Route('/livre/edit/{id}', name: 'edit_livre', methods: ['PUT'])]
    public function editLivre(int $id, Request $request, EntityManagerInterface $entityManagerInterface): Response
    {
        $livre = $entityManagerInterface->getRepository(Livre::class)->find($id);

        if (!$livre) {
            return new Response('Livre non trouvé', 404);
        }

        $content = json_decode($request->getContent(), true);

        if (isset($content['titre'])) {
            $livre->setTitre($content['titre']);
        }
        if (isset($content['datePublication'])) {
            $livre->setDatePublication(new \DateTime($content['datePublication']));
        }
        if (isset($content['disponible'])) {
            $livre->setDisponible($content['disponible']);
        }

        $entityManagerInterface->flush();

        return new Response('Livre mis à jour avec succès');
    }
   
    
    #[Route('/livre/delete/{id}', name: 'delete_livre', methods: ['DELETE'])]
    public function deleteLivre(int $id, EntityManagerInterface $entityManagerInterface): Response
    {
        $livre = $entityManagerInterface->getRepository(Livre::class)->find($id);

        if (!$livre) {
            return new Response('Livre non trouvé', 404);
        }

        $entityManagerInterface->remove($livre);
        $entityManagerInterface->flush();

        return new Response('Livre supprimé avec succès');
    }
}
