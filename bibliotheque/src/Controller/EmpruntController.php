<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\Utilisateur;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmpruntController extends AbstractController
{
    public function __construct(private LoggerInterface $logger) {}

    #[Route('/emprunt/add', name: 'add_emprunt', methods: ['POST'])]
    public function addEmprunt(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Ensure JSON body
        $content = json_decode((string)$request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Payload JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateurId = $content['utilisateur_id'] ?? null;
        $livreId = $content['livre_id'] ?? null;

        if (!is_numeric($utilisateurId) || !is_numeric($livreId)) {
            return $this->json(['error' => 'utilisateur_id et livre_id doivent être des entiers'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $utilisateurId = (int)$utilisateurId;
        $livreId = (int)$livreId;

        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($utilisateurId);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $livre = $entityManager->getRepository(Livre::class)->find($livreId);
        if (!$livre) {
            return $this->json(['error' => 'Livre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$livre->isDisponible()) {
            return $this->json(['error' => 'Ce livre est déjà emprunté'], Response::HTTP_CONFLICT);
        }

        $nbEmprunts = $entityManager->getRepository(Emprunt::class)->count([
            'utilisateur' => $utilisateur,
            'rendu' => false,
        ]);
        if ($nbEmprunts >= 4) {
            return $this->json(['error' => "L'utilisateur a déjà 4 emprunts en cours"], Response::HTTP_CONFLICT);
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();
        try {
            
            $em = $entityManager;
            $livreLocked = $em->find(Livre::class, $livreId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

            if (!$livreLocked->isDisponible()) {
                $conn->rollBack();
                return $this->json(['error' => 'Ce livre est déjà emprunté (conflit)'], Response::HTTP_CONFLICT);
            }

            $emprunt = new Emprunt();
            $emprunt->setUtilisateur($utilisateur);
            $emprunt->setLivre($livreLocked);
            $emprunt->setDateEmprunt(new \DateTime());
            $emprunt->setRendu(false);

            $livreLocked->setDisponible(false);

            $em->persist($emprunt);
            $em->flush();
            $conn->commit();

            return $this->json([
                'message' => 'Livre emprunté avec succès',
                'emprunt_id' => $emprunt->getId()
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'ajout d\'un emprunt: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            
            return $this->json(['error' => 'Impossible de créer l\'emprunt, voir logs'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/emprunt/rendre/{id}', name: 'rendre_emprunt', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function rendreLivre(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $emprunt = $entityManager->getRepository(Emprunt::class)->find($id);

        if (!$emprunt) {
            return $this->json(['error' => 'Emprunt non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($emprunt->isRendu()) {
            return $this->json(['error' => 'Ce livre a déjà été rendu'], Response::HTTP_CONFLICT);
        }

        try {
            $emprunt->setRendu(true);
            $emprunt->setDateRetour(new \DateTime());
            $livre = $emprunt->getLivre();
            if ($livre) {
                $livre->setDisponible(true);
            }

            $entityManager->flush();

            return $this->json(['message' => 'Livre rendu avec succès'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du rendu: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->json(['error' => 'Impossible de marquer le livre comme rendu, voir logs'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/emprunts/utilisateur/{id}', name: 'get_emprunts_utilisateur', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEmpruntsUtilisateur(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $emprunts = $entityManager->getRepository(Emprunt::class)->findBy(
            ['utilisateur' => $utilisateur, 'rendu' => false],
            ['dateEmprunt' => 'ASC']
        );

        $data = [];
        foreach ($emprunts as $emprunt) {
            $livre = $emprunt->getLivre();
            $data[] = [
                'id' => $emprunt->getId(),
                'livre_id' => $livre ? $livre->getId() : null,
                'livre_titre' => $livre ? $livre->getTitre() : null,
                'dateEmprunt' => $emprunt->getDateEmprunt()?->format('Y-m-d'),
            ];
        }

        return $this->json([
            'utilisateur' => [
                'id' => $utilisateur->getId(),
                'nom_complet' => trim(($utilisateur->getPrenom() ?? '') . ' ' . ($utilisateur->getNom() ?? ''))
            ],
            'nombre_emprunts_en_cours' => count($data),
            'emprunts' => $data,
        ], Response::HTTP_OK);
    }

    #[Route('/emprunts/livres-auteur', name: 'livres_auteur_dates', methods: ['GET'])]
    public function getLivresEmpruntesParAuteur(Request $request, EmpruntRepository $empruntRepository): JsonResponse
    {
        $auteurIdRaw = $request->query->get('auteurId');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        if ($auteurIdRaw === null || $dateDebut === null || $dateFin === null) {
            return $this->json(['error' => 'Paramètres manquants (auteurId, dateDebut, dateFin requis)'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($auteurIdRaw)) {
            return $this->json(['error' => 'auteurId doit être un entier'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $auteurId = (int)$auteurIdRaw;

        try {
            $dateDebutObj = new \DateTime($dateDebut);
            $dateFinObj = new \DateTime($dateFin);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide (utiliser YYYY-MM-DD)'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $emprunts = $empruntRepository->findLivresEmpruntesParAuteurEntreDates($auteurId, $dateDebutObj, $dateFinObj);

            $data = [];
            foreach ($emprunts as $emprunt) {
                $livre = $emprunt->getLivre();
                if (!$livre) {
                    continue;
                }
                $auteur = method_exists($livre, 'getIdAuteur') ? $livre->getIdAuteur() : null;

                $data[$livre->getId()] = [
                    'id' => $livre->getId(),
                    'titre' => $livre->getTitre(),
                    'auteurId' => $auteur ? $auteur->getId() : null,
                    'auteurNom' => $auteur ? $auteur->getNom() : null,
                ];
            }

            return $this->json(array_values($data), Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur recherche livres par auteur: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->json(['error' => 'Erreur interne lors de la recherche, voir logs'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}