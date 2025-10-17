<?php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
class Emprunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    private ?livre $livre = null;

    #[ORM\ManyToOne(inversedBy: 'emprunt')]
    private ?utilisateur $utilisateur = null;

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

    public function getLivre(): ?livre
    {
        return $this->livre;
    }

    public function setLivre(?livre $livre): static
    {
        $this->livre = $livre;

        return $this;
    }

    public function getUtilisateur(): ?utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?utilisateur $utilisateur): static
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
