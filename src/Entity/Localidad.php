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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Circunscripcion", inversedBy="localidad")
     */
    private $circunscripcion;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organismo", mappedBy="localidad")
     */
    private $organismos;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $feriadosLocales;

    public function __construct()
    {
        $this->oficinas = new ArrayCollection();
        $this->organismos = new ArrayCollection();
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

    public function getCircunscripcion(): ?Circunscripcion
    {
        return $this->circunscripcion;
    }

    public function setCircunscripcion(?Circunscripcion $circunscripcion): self
    {
        $this->circunscripcion = $circunscripcion;

        return $this;
    }

    /**
     * @return Collection|Organismo[]
     */
    public function getOrganismos(): Collection
    {
        return $this->organismos;
    }

    public function addOrganismo(Organismo $organismo): self
    {
        if (!$this->organismos->contains($organismo)) {
            $this->organismos[] = $organismo;
            $organismo->setLocalidad($this);
        }

        return $this;
    }

    public function removeOrganismo(Organismo $organismo): self
    {
        if ($this->organismos->contains($organismo)) {
            $this->organismos->removeElement($organismo);
            // set the owning side to null (unless already changed)
            if ($organismo->getLocalidad() === $this) {
                $organismo->setLocalidad(null);
            }
        }

        return $this;
    }

    public function getFeriadosLocales(): ?string
    {
        return $this->feriadosLocales;
    }

    public function setFeriadosLocales(?string $feriadosLocales): self
    {
        $this->feriadosLocales = $feriadosLocales;

        return $this;
    }

    public function getFeriadosLocalesConAnio(string $anio = ''): ?string
    {
        if (!$this->feriadosLocales)
            return '';

        if (!$anio)
            $anio = date('Y');

        return str_replace(',' , '/' . $anio . ',', $this->feriadosLocales) . '/' . $anio;
    }

}
