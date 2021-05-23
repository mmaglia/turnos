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

    /**
     * Retorna lista de Circunscripciones en función a la Zona (Norte o Sur)
     * Si la Circunscripción es 1, retorna lista Zona Norte
     * Si la Circunscripción es 2, retornar lista Zona Sur
     * Cualquier otro valor retornará la circunscripción o null
     */
    public function getCircunscripcionesListByZona(): ?string
    {

        if ($this->getId() == 1 || $this->getId() == 4 || $this->getId() == 5)
            return '1,4,5';

        if ($this->getId() == 2 || $this->getId() == 3)
            return '2,3';

        return $this->getId();
    }


}
