<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Auteur;
use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LivreController extends AbstractController
{
    #[Route('/livre/add', name: 'add_livre', methods: ['POST'])]
    public function addLivre(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $content = json_decode($request->getContent(), true);

        if (empty($content['titre']) || empty($content['datePublication']) || !isset($content['disponible']) || empty($content['idAuteur']) || empty($content['categorie'])) {
            return $this->json(['error' => 'Données incomplètes'], 400);
        }

        $auteur = $entityManager->getRepository(Auteur::class)->find($content['idAuteur']);
        if (!$auteur) {
            $logger->error('Auteur non trouvé', ['idAuteur' => $content['idAuteur']]);
            return $this->json(['error' => 'Auteur non trouvé'], 404);
        }

        $categorie = $entityManager->getRepository(Categorie::class)->find($content['categorie']);
        if (!$categorie) {
            $logger->error('Catégorie non trouvée', ['categorie' => $content['categorie']]);
            return $this->json(['error' => 'Catégorie non trouvée'], 404);
        }

        try {
            $date = new \DateTime($content['datePublication']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Date invalide'], 400);
        }

        $livre = new Livre();
        $livre->setTitre($content['titre']);
        $livre->setDatePublication($date);
        $livre->setDisponible($content['disponible']);
        $livre->setIdAuteur($auteur);
        $livre->setCategorie($categorie);

        $entityManager->persist($livre);
        $entityManager->flush();

        $logger->info('Livre ajouté avec succès', ['livreId' => $livre->getId()]);

        return $this->json([
            'message' => 'Livre ajouté avec succès',
            'livre' => [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'datePublication' => $livre->getDatePublication()->format('Y-m-d'),
                'disponible' => $livre->isDisponible(),
                'idAuteur' => $livre->getIdAuteur()->getId(),
                'categorie' => $livre->getCategorie()->getId(),
            ]
        ], 201);
    }

    #[Route(path: '/livre/{id}', name: 'get_livre', methods: ['GET'])]
    public function getLivre(int $id, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $livre = $entityManager->getRepository(Livre::class)->find($id);
        if (!$livre) {
            $logger->warning('Livre non trouvé', ['livreId' => $id]);
            return $this->json(['error' => 'Livre non trouvé'], 404);
        }

        return $this->json([
            'id' => $livre->getId(),
            'titre' => $livre->getTitre(),
            'datePublication' => $livre->getDatePublication()->format('Y-m-d'),
            'disponible' => $livre->isDisponible(),
            'idAuteur' => $livre->getIdAuteur()->getId(),
            'categorie' => $livre->getCategorie()->getId(),
        ], 200);
    }

    #[Route('/livres', name: 'get_all_livres', methods: ['GET'])]
    public function getAllLivres(EntityManagerInterface $entityManager, LoggerInterface $logger): Response
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

        $logger->info('Liste de livres récupérée', ['count' => count($livres)]);

        return $this->json($data, 200);
    }

    #[Route('/livre/edit/{id}', name: 'edit_livre', methods: ['PUT'])]
    public function editLivre(int $id, Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $livre = $entityManager->getRepository(Livre::class)->find($id);
        if (!$livre) {
            $logger->warning('Livre non trouvé pour modification', ['livreId' => $id]);
            return $this->json(['error' => 'Livre non trouvé'], 404);
        }

        $content = json_decode($request->getContent(), true);

        if (isset($content['titre'])) {
            $livre->setTitre($content['titre']);
        }
        if (isset($content['datePublication'])) {
            try {
                $livre->setDatePublication(new \DateTime($content['datePublication']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Date invalide'], 400);
            }
        }
        if (isset($content['disponible'])) {
            $livre->setDisponible($content['disponible']);
        }

        if (isset($content['idAuteur'])) {
            $auteur = $entityManager->getRepository(Auteur::class)->find($content['idAuteur']);
            if (!$auteur) return $this->json(['error' => 'Auteur non trouvé'], 404);
            $livre->setIdAuteur($auteur);
        }

        if (isset($content['categorie'])) {
            $categorie = $entityManager->getRepository(Categorie::class)->find($content['categorie']);
            if (!$categorie) return $this->json(['error' => 'Catégorie non trouvée'], 404);
            $livre->setCategorie($categorie);
        }

        $entityManager->flush();
        $logger->info('Livre mis à jour avec succès', ['livreId' => $livre->getId()]);

        return $this->json([
            'message' => 'Livre mis à jour avec succès',
            'livre' => [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'datePublication' => $livre->getDatePublication()->format('Y-m-d'),
                'disponible' => $livre->isDisponible(),
                'idAuteur' => $livre->getIdAuteur()->getId(),
                'categorie' => $livre->getCategorie()->getId(),
            ]
        ], 200);
    }

    #[Route('/livre/delete/{id}', name: 'delete_livre', methods: ['DELETE'])]
    public function deleteLivre(int $id, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $livre = $entityManager->getRepository(Livre::class)->find($id);
        if (!$livre) {
            $logger->warning('Livre non trouvé pour suppression', ['livreId' => $id]);
            return $this->json(['error' => 'Livre non trouvé'], 404);
        }

        $entityManager->remove($livre);
        $entityManager->flush();

        $logger->info('Livre supprimé avec succès', ['livreId' => $id]);

        return $this->json(['message' => 'Livre supprimé avec succès'], 200);
    }
}
