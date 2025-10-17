<?php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Livre;
use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
class Emprunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    private ?Livre $livre = null;

    #[ORM\ManyToOne(inversedBy: 'emprunt')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column]
    private ?\DateTime $dateEmprunt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateRetour = null;

    #[ORM\Column]
    private ?bool $rendu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): static
    {
        $this->livre = $livre;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDateEmprunt(): ?\DateTime
    {
        return $this->dateEmprunt;
    }

    public function setDateEmprunt(\DateTime $dateEmprunt): static
    {
        $this->dateEmprunt = $dateEmprunt;

        return $this;
    }

    public function getDateRetour(): ?\DateTime
    {
        return $this->dateRetour;
    }

    public function setDateRetour(?\DateTime $dateRetour): static
    {
        $this->dateRetour = $dateRetour;

        return $this;
    }

    public function isRendu(): ?bool
    {
        return $this->rendu;
    }

    public function setRendu(bool $rendu): static
    {
        $this->rendu = $rendu;

        return $this;
    }
}
