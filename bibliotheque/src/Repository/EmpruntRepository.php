<?php

namespace App\Repository;

use App\Entity\Emprunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emprunt>
 */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }


    public function findLivresEmpruntesParAuteurEntreDates(int $auteurId, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.livre', 'l')
            ->join('l.id_auteur', 'a')
            ->where('a.id = :auteurId')
            ->andWhere('e.dateEmprunt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('auteurId', $auteurId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getResult();
    }



}
