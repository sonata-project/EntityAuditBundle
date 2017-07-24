<?php declare(strict_types=1);


namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Issue273Product
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", name="code_product")
     */
    private $id;

    /**
     * @var Issue273Category
     *
     * @ORM\ManyToOne(targetEntity="SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue273Category")
     * @ORM\JoinColumn(name="code_category", referencedColumnName="code_category", nullable=false)
     */
    private $category;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Issue273Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Issue273Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
}