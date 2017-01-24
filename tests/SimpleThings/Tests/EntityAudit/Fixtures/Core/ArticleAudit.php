<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;
use SimpleThings\EntityAudit\Mapping\Annotation\Ignore;

/**
 * @Auditable()
 * @ORM\Entity
 */
class ArticleAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string", name="my_title_column") */
    private $title;

    /** @ORM\Column(type="text") */
    private $text;

    /**
     * @Ignore()
     * @ORM\Column(type="text")
     */
    private $ignoreMe;

    /** @ORM\ManyToOne(targetEntity="UserAudit") */
    private $author;

    public function __construct($title, $text, $author, $ignoreMe)
    {
        $this->title    = $title;
        $this->text     = $text;
        $this->author   = $author;
        $this->ignoreMe = $ignoreMe;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function setIgnoreMe($ignoreMe)
    {
        $this->ignoreMe = $ignoreMe;
    }
}
