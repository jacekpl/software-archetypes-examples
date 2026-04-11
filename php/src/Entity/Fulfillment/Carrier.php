<?php

namespace App\Entity\Fulfillment;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'carrier')]
class Carrier
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 20, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingUrlTemplate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public function __construct(string $code, string $name, ?string $trackingUrlTemplate = null)
    {
        $this->id = Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->trackingUrlTemplate = $trackingUrlTemplate;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTrackingUrl(string $trackingNumber): ?string
    {
        if ($this->trackingUrlTemplate === null) {
            return null;
        }
        return str_replace('{tracking}', $trackingNumber, $this->trackingUrlTemplate);
    }
}
