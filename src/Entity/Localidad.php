<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LocalidadRepository")
 */
class Localidad
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=80)
     */
    private $localidad;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Oficina", mappedBy="localidad")
     */
    private $oficinas;

    public function __construct()
    {
        $this->oficinas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocalidad(): ?string
    {
        return $this->localidad;
    }

    public function setLocalidad(string $localidad): self
    {
        $this->localidad = $localidad;

        return $this;
    }

    /**
     * @return Collection|Oficina[]
     */
    public function getOficinas(): Collection
    {
        return $this->oficinas;
    }

    public function addOficina(Oficina $oficina): self
    {
        if (!$this->oficinas->contains($oficina)) {
            $this->oficinas[] = $oficina;
            $oficina->setLocalidad($this);
        }

        return $this;
    }

    public function removeOficina(Oficina $oficina): self
    {
        if ($this->oficinas->contains($oficina)) {
            $this->oficinas->removeElement($oficina);
            // set the owning side to null (unless already changed)
            if ($oficina->getLocalidad() === $this) {
                $oficina->setLocalidad(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->localidad;
    }
}
