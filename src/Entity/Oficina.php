<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OficinaRepository")
 */
class Oficina
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=120)
     */
    private $oficina;

    /**
     * @ORM\Column(type="time")
     */
    private $horaInicioAtencion;

    /**
     * @ORM\Column(type="time")
     */
    private $horaFinAtencion;

    /**
     * @ORM\Column(type="smallint")
     */
    private $frecuenciaAtencion;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Localidad", inversedBy="oficinas")
     */
    private $localidad;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Turno", mappedBy="oficina")
     */
    private $turnos;

    public function __construct()
    {
        $this->turnos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOficina(): ?string
    {
        return $this->oficina;
    }

    public function setOficina(string $oficina): self
    {
        $this->oficina = $oficina;

        return $this;
    }

    public function getHoraInicioAtencion(): ?\DateTimeInterface
    {
        return $this->horaInicioAtencion;
    }

    public function setHoraInicioAtencion(\DateTimeInterface $horaInicioAtencion): self
    {
        $this->horaInicioAtencion = $horaInicioAtencion;

        return $this;
    }

    public function getHoraFinAtencion(): ?\DateTimeInterface
    {
        return $this->horaFinAtencion;
    }

    public function setHoraFinAtencion(\DateTimeInterface $horaFinAtencion): self
    {
        $this->horaFinAtencion = $horaFinAtencion;

        return $this;
    }

    public function getFrecuenciaAtencion(): ?int
    {
        return $this->frecuenciaAtencion;
    }

    public function setFrecuenciaAtencion(int $frecuenciaAtencion): self
    {
        $this->frecuenciaAtencion = $frecuenciaAtencion;

        return $this;
    }

    public function getLocalidad(): ?Localidad
    {
        return $this->localidad;
    }

    public function setLocalidad(?Localidad $localidad): self
    {
        $this->localidad = $localidad;

        return $this;
    }

    /**
     * @return Collection|Turno[]
     */
    public function getTurnos(): Collection
    {
        return $this->turnos;
    }

    public function addTurno(Turno $turno): self
    {
        if (!$this->turnos->contains($turno)) {
            $this->turnos[] = $turno;
            $turno->setOficina($this);
        }

        return $this;
    }

    public function removeTurno(Turno $turno): self
    {
        if ($this->turnos->contains($turno)) {
            $this->turnos->removeElement($turno);
            // set the owning side to null (unless already changed)
            if ($turno->getOficina() === $this) {
                $turno->setOficina(null);
            }
        }

        return $this;
    }
}
