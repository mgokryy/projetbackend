<?php

namespace App\Entity;

use App\Repository\AuteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuteurRepository::class)]
class Auteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $biographie = null;

    #[ORM\Column]
    private ?\DateTime $dateNaissance = null;

    /**
     * @var Collection<int, Livre>
     */
    #[ORM\OneToMany(targetEntity: Livre::class, mappedBy: 'id_auteur')]
    private Collection $id_livre;

    public function __construct()
    {
        $this->id_livre = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getBiographie(): ?string
    {
        return $this->biographie;
    }

    public function setBiographie(string $biographie): static
    {
        $this->biographie = $biographie;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * @return Collection<int, Livre>
     */
    public function getIdLivre(): Collection
    {
        return $this->id_livre;
    }

    public function addIdLivre(Livre $idLivre): static
    {
        if (!$this->id_livre->contains($idLivre)) {
            $this->id_livre->add($idLivre);
            $idLivre->setIdAuteur($this);
        }

        return $this;
    }

    public function removeIdLivre(Livre $idLivre): static
    {
        if ($this->id_livre->removeElement($idLivre)) {
            // set the owning side to null (unless already changed)
            if ($idLivre->getIdAuteur() === $this) {
                $idLivre->setIdAuteur(null);
            }
        }

        return $this;
    }
}
