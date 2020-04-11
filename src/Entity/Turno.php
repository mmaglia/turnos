<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TurnoRepository")
 */
class Turno
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaHora;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Persona", inversedBy="turnos")
     */
    private $persona;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Oficina", inversedBy="turnos")
     */
    private $oficina;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaHora(): ?\DateTimeInterface
    {
        return $this->fechaHora;
    }

    public function setFechaHora(\DateTimeInterface $fechaHora): self
    {
        $this->fechaHora = $fechaHora;

        return $this;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function setMotivo(?string $motivo): self
    {
        $this->motivo = $motivo;

        return $this;
    }

    public function getPersona(): ?Persona
    {
        return $this->persona;
    }

    public function setPersona(?Persona $persona): self
    {
        $this->persona = $persona;

        return $this;
    }

    public function getOficina(): ?Oficina
    {
        return $this->oficina;
    }

    public function setOficina(?Oficina $oficina): self
    {
        $this->oficina = $oficina;

        return $this;
    }
}
