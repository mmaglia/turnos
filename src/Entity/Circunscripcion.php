<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CircunscripcionRepository")
 */
class Circunscripcion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $circunscripcion;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Localidad", mappedBy="circunscripcion")
     */
    private $localidad;

    public function __construct()
    {
        $this->localidad = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCircunscripcion(): ?string
    {
        return $this->circunscripcion;
    }

    public function setCircunscripcion(string $circunscripcion): self
    {
        $this->circunscripcion = $circunscripcion;

        return $this;
    }

    /**
     * @return Collection|Localidad[]
     */
    public function getLocalidad(): Collection
    {
        return $this->localidad;
    }

    public function addLocalidad(Localidad $localidad): self
    {
        if (!$this->localidad->contains($localidad)) {
            $this->localidad[] = $localidad;
            $localidad->setCircunscripcion($this);
        }

        return $this;
    }

    public function removeLocalidad(Localidad $localidad): self
    {
        if ($this->localidad->contains($localidad)) {
            $this->localidad->removeElement($localidad);
            // set the owning side to null (unless already changed)
            if ($localidad->getCircunscripcion() === $this) {
                $localidad->setCircunscripcion(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->circunscripcion;
    }

}
