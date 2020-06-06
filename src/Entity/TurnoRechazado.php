<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TurnoRechazadoRepository")
 */
class TurnoRechazado
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
    private $fechaHoraRechazo;


    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaHoraTurno;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Persona")
     */
    private $persona;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Oficina")
     */
    private $oficina;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $motivoRechazo;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $emailEnviado;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $notebook;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $zoom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaHoraRechazo(): ?\DateTimeInterface
    {
        return $this->fechaHoraRechazo;
    }

    public function setFechaHoraRechazo(\DateTimeInterface $fechaHoraRechazo): self
    {
        $this->fechaHoraRechazo = $fechaHoraRechazo;

        return $this;
    }


    public function getFechaHoraTurno(): ?\DateTimeInterface
    {
        return $this->fechaHoraTurno;
    }

    public function setFechaHoraTurno(\DateTimeInterface $fechaHoraTurno): self
    {
        $this->fechaHoraTurno = $fechaHoraTurno;

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

    public function getMotivoRechazo(): ?string
    {
        return $this->motivoRechazo;
    }

    public function setMotivoRechazo(?string $motivoRechazo): self
    {
        $this->motivoRechazo = $motivoRechazo;

        return $this;
    }

    public function getEmailEnviado(): ?bool
    {
        return $this->emailEnviado;
    }

    public function setEmailEnviado(?bool $emailEnviado): self
    {
        $this->emailEnviado = $emailEnviado;

        return $this;
    }

    public function getNotebook(): ?bool
    {
        return $this->notebook;
    }

    public function setNotebook(bool $notebook): self
    {
        $this->notebook = $notebook;

        return $this;
    }

    public function getZoom(): ?bool
    {
        return $this->zoom;
    }

    public function setZoom(bool $zoom): self
    {
        $this->zoom = $zoom;

        return $this;
    }
}
