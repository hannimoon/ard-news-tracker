<?php

// src/Entity/CronConfig.php
namespace App\Entity;

use App\Repository\CronConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CronConfigRepository::class)]
/**
 * Entity representing cron configuration for news import scheduling.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class CronConfig {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 5, options: ['default' => '03:00'])]
    private string $scheduleTime = '03:00';

    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;


    // Getter & Setter...
    public function getId(): ?int { return $this->id; }
    public function getScheduleTime(): string { return $this->scheduleTime; }
    public function setScheduleTime(string $time): self { $this->scheduleTime = $time; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
