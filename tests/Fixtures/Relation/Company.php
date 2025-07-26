<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress ClassMustBeFinal
 */
#[ORM\Entity]
class Company
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(length: 40)]
    protected ?string $name = null;

    /**
     * @var Collection<int, DocumentType>
     */
    #[ORM\ManyToMany(targetEntity: DocumentType::class, inversedBy: 'companies')]
    #[ORM\JoinTable(name: 'company_document_types')]
    #[ORM\JoinColumn(name: 'company_id')]
    #[ORM\InverseJoinColumn(name: 'document_type_id')]
    protected Collection $documentTypes;

    public function __construct()
    {
        $this->documentTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, DocumentType>
     */
    public function getDocumentTypes(): Collection
    {
        return $this->documentTypes;
    }

    public function addDocumentType(DocumentType $documentType): self
    {
        if (!$this->documentTypes->contains($documentType)) {
            $this->documentTypes[] = $documentType;
            $documentType->addCompany($this);
        }

        return $this;
    }
}
