<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[OA\Schema(description: "One news item with all relevant information.")]
/**
 * Entity representing a news item.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[OA\Property(description: "The unique ID", example: 1)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    #[OA\Property(description: "Publishing date", type: "string", format: "date")]
    private ?\DateTime $datePublished = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    #[OA\Property(description: "Sentiment of news (good, bad, neutral)", type: "string", example: "good")]
    private ?string $sentiment = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;
    
    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: false,
        columnDefinition: "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER date_published"
    )]
    
    private \DateTimeImmutable $importedAt;

    public function __construct()
    {
        $this->importedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getDatePublished(): ?\DateTime
    {
        return $this->datePublished;
    }

    public function setDatePublished(\DateTime $datePublished): self
    {
        $this->datePublished = $datePublished;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(string $sentiment): self
    {
        $this->sentiment = $sentiment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeImmutable $importedAt): self
    {
        $this->importedAt = $importedAt;
        return $this;
    }
}
