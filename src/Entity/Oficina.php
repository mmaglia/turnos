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
     * @ORM\Column(type="smallint", nullable=true, options={"default" : 1})
     */
    private $cantidadTurnosxturno;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Localidad", inversedBy="oficinas")
     */
    private $localidad;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Turno", mappedBy="oficina")
     */
    private $turnos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Usuario", mappedBy="oficina")
     */
    private $usuarios;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $telefono;

    /**
     * @ORM\Column(type="boolean")
     */
    private $habilitada;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $autoExtend;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $autoGestion;

    public function __construct()
    {
        $this->turnos = new ArrayCollection();
        $this->usuarios = new ArrayCollection();
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

    public function getCantidadTurnosxturno(): ?int
    {
        return $this->cantidadTurnosxturno;
    }

    public function setCantidadTurnosxturno(int $cantidadTurnosxturno): self
    {
        $this->cantidadTurnosxturno = $cantidadTurnosxturno;

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

    public function __toString()
    {
        return $this->oficina . ' (' . $this->getLocalidad() . ')';
    }

    /**
     * @return Collection|Usuario[]
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }

    public function addUsuario(Usuario $usuario): self
    {
        if (!$this->usuarios->contains($usuario)) {
            $this->usuarios[] = $usuario;
            $usuario->setOficina($this);
        }

        return $this;
    }

    public function removeUsuario(Usuario $usuario): self
    {
        if ($this->usuarios->contains($usuario)) {
            $this->usuarios->removeElement($usuario);
            // set the owning side to null (unless already changed)
            if ($usuario->getOficina() === $this) {
                $usuario->setOficina(null);
            }
        }

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): self
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getOficinayLocalidad() {
        return $this->oficina . ' (' . $this->getLocalidad() . ')';

    }

    public function getHabilitada(): ?bool
    {
        return $this->habilitada;
    }

    public function setHabilitada(bool $habilitada): self
    {
        $this->habilitada = $habilitada;

        return $this;
    }

    public function getAutoExtend(): ?bool
    {
        return $this->autoExtend;
    }

    public function setAutoExtend(?bool $autoExtend): self
    {
        $this->autoExtend = $autoExtend;

        return $this;
    }

    public function getAutoGestion(): ?bool
    {
        return $this->autoGestion;
    }

    public function setAutoGestion(?bool $autoGestion): self
    {
        $this->autoGestion = $autoGestion;

        return $this;
    }

}
