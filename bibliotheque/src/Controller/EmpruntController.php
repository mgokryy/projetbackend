<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\Utilisateur;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmpruntController extends AbstractController
{
    #[Route('/emprunt/add', name: 'add_emprunt', methods: ['POST'])]
    public function addEmprunt(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        $utilisateurId = $content['utilisateur_id'] ?? null;
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($utilisateurId);
        if (!$utilisateur) {
            return new Response('Utilisateur non trouvé', 404);
        }

    
        $livreId = $content['livre_id'] ?? null;
        $livre = $entityManager->getRepository(Livre::class)->find($livreId);
        if (!$livre) {
            return new Response('Livre non trouvé', 404);
        }

       
        if (!$livre->isDisponible()) {
            return new Response('Ce livre est déjà emprunté', 400);
        }

        $nbEmprunts = $entityManager->getRepository(Emprunt::class)->count([
            'utilisateur' => $utilisateur,
            'rendu' => false,
        ]);
        if ($nbEmprunts >= 4) {
            return new Response('L’utilisateur a déjà 4 emprunts en cours', 400);
        }

        $emprunt = new Emprunt();
        $emprunt->setUtilisateur($utilisateur);
        $emprunt->setLivre($livre);
        $emprunt->setDateEmprunt(new \DateTime());
        $emprunt->setRendu(false);

        
        $livre->setDisponible(false);

        $entityManager->persist($emprunt);
        $entityManager->flush();

        return new Response('Livre emprunté avec succès (id emprunt : ' . $emprunt->getId() . ')', 201);
    }

    
    #[Route('/emprunt/rendre/{id}', name: 'rendre_emprunt', methods: ['PUT'])]
    public function rendreLivre(int $id, EntityManagerInterface $entityManager): Response
    {
        $emprunt = $entityManager->getRepository(Emprunt::class)->find($id);

        if (!$emprunt) {
            return new Response('Emprunt non trouvé', 404);
        }

        if ($emprunt->isRendu()) {
            return new Response('Ce livre a déjà été rendu', 400);
        }

        $emprunt->setRendu(true);
        $emprunt->setDateRetour(new \DateTime());
        $emprunt->getLivre()->setDisponible(true);

        $entityManager->flush();

        return new Response('Livre rendu avec succès', 200);
    }

    
    #[Route('/emprunts/utilisateur/{id}', name: 'get_emprunts_utilisateur', methods: ['GET'])]
    public function getEmpruntsUtilisateur(int $id, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$utilisateur) {
            return new Response('Utilisateur non trouvé', 404);
        }

        $emprunts = $entityManager->getRepository(Emprunt::class)->findBy(
            ['utilisateur' => $utilisateur, 'rendu' => false],
            ['dateEmprunt' => 'ASC']
        );

        $data = [];
        foreach ($emprunts as $emprunt) {
            $data[] = [
                'id' => $emprunt->getId(),
                'livre' => $emprunt->getLivre()->getTitre(),
                'dateEmprunt' => $emprunt->getDateEmprunt()->format('Y-m-d'),
            ];
        }

        return $this->json([
            'utilisateur' => $utilisateur->getPrenom() . ' ' . $utilisateur->getNom(),
            'nombre_emprunts_en_cours' => count($data),
            'emprunts' => $data,
        ]);
    }

    // route pour récupérer tous les livres d’un auteur emprunté entre 2 dates définies
    #[Route('/emprunts/livres-auteur', name: 'livres_auteur_dates', methods: ['GET'])]
    public function getLivresEmpruntesParAuteur(Request $request, EmpruntRepository $empruntRepository): Response
    {
        $auteurId = $request->query->get('auteurId');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        if (!$auteurId || !$dateDebut || !$dateFin) {
            return new Response('Paramètres manquants', 400);
        }

        try {
            $dateDebutObj = new \DateTime($dateDebut);
            $dateFinObj = new \DateTime($dateFin);
        } catch (\Exception $e) {
            return new Response('Format de date invalide', 400);
        }

        $emprunts = $empruntRepository->findLivresEmpruntesParAuteurEntreDates((int)$auteurId, $dateDebutObj, $dateFinObj);

        $data = [];
        foreach ($emprunts as $emprunt) {
            $livre = $emprunt->getLivre();
            $data[$livre->getId()] = [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'auteurId' => $livre->getIdAuteur()->getId(),
                'auteurNom' => $livre->getIdAuteur()->getNom()
            ];
        }

        return $this->json(array_values($data));
    }


}