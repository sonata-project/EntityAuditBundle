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

namespace Sonata\EntityAuditBundle\Tests\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Collection\AuditedCollection;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\PageLocalization;

final class AuditedCollectionTest extends TestCase
{
    public function testMatchingLoadsEntitiesAtTheRequestedRevisionAndPreservesKeys(): void
    {
        $gb = new PageLocalization('en-GB');
        $us = new PageLocalization('en-US');
        $de = new PageLocalization('de-DE');
        $collection = $this->createCollection([$gb, $us, $de]);

        $matches = $collection->matching(CriteriaFactory::create()->where(Criteria::expr()->startsWith('locale', 'en-')));

        static::assertSame(['en-GB' => $gb, 'en-US' => $us], $matches->toArray());
        static::assertSame(['en-GB' => $gb, 'en-US' => $us, 'de-DE' => $de], $collection->toArray());

        $matches->clear();

        static::assertCount(3, $collection);
    }

    public function testMatchingSortsAndPaginatesWithoutChangingTheCollection(): void
    {
        $us = new PageLocalization('en-US');
        $de = new PageLocalization('de-DE');
        $gb = new PageLocalization('en-GB');
        $collection = $this->createCollection([$us, $de, $gb]);
        $criteria = CriteriaFactory::create()
            ->orderBy(['locale' => CriteriaFactory::descendingOrder()])
            ->setFirstResult(1)
            ->setMaxResults(1);

        static::assertSame(['en-GB' => $gb], $collection->matching($criteria)->toArray());
        static::assertSame(['en-US' => $us, 'de-DE' => $de, 'en-GB' => $gb], $collection->toArray());
    }

    public function testMatchingReturnsAnEmptyCollectionWhenNothingMatches(): void
    {
        $gb = new PageLocalization('en-GB');
        $collection = $this->createCollection([$gb]);

        static::assertSame([], $collection->matching(CriteriaFactory::create()->where(Criteria::expr()->eq('locale', 'fr-FR')))->toArray());
        static::assertSame(['en-GB' => $gb], $collection->toArray());
    }

    public function testMatchingAnEmptyCollection(): void
    {
        $collection = $this->createCollection([]);

        static::assertSame([], $collection->matching(CriteriaFactory::create())->toArray());
        static::assertTrue($collection->isEmpty());
    }

    public function testCollectionReadOperationsRetainTheirResults(): void
    {
        $gb = new PageLocalization('en-GB');
        $us = new PageLocalization('en-US');
        $collection = $this->createCollection([$gb, $us]);

        static::assertFalse($collection->isEmpty());
        static::assertCount(2, $collection);
        static::assertTrue($collection->contains($gb));
        static::assertTrue($collection->containsKey('en-US'));
        static::assertSame($gb, $collection->get('en-GB'));
        static::assertSame($us, $collection['en-US']);
        static::assertSame(['en-GB', 'en-US'], $collection->getKeys());
        static::assertSame([$gb, $us], $collection->getValues());
        static::assertSame($gb, $collection->first());
        static::assertSame('en-GB', $collection->key());
        static::assertSame($gb, $collection->current());
        static::assertSame($us, $collection->next());
        static::assertSame($us, $collection->last());
        static::assertSame('en-US', $collection->indexOf($us));
        static::assertSame(['en-US' => $us], $collection->slice(1));
        static::assertSame(['en-GB' => $gb, 'en-US' => $us], iterator_to_array($collection));
        static::assertSame(['en-US' => $us], $collection->filter(static fn (PageLocalization $entity): bool => $entity === $us)->toArray());
        static::assertSame(['en-GB' => 'en-GB', 'en-US' => 'en-US'], $collection->map(static fn (PageLocalization $entity): ?string => $entity->getLocale())->toArray());
    }

    public function testAddingToTheCollectionStillThrows(): void
    {
        $collection = $this->createCollection([]);

        $this->expectException(AuditedCollectionException::class);
        $this->expectExceptionMessage('The AuditedCollection is read-only');

        $collection->add(new PageLocalization('en-GB'));
    }

    /**
     * @param list<PageLocalization> $entities
     *
     * @return AuditedCollection<string, PageLocalization>
     */
    private function createCollection(array $entities): AuditedCollection
    {
        $rows = [];
        $entitiesById = [];
        foreach ($entities as $id => $entity) {
            $rows[] = ['id' => $id, 'locale' => $entity->getLocale(), 'rev' => 42];
            $entitiesById[$id] = $entity;
        }

        $connection = static::createStub(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn($rows);

        $reader = static::createStub(AuditReader::class);
        $reader->method('getConfiguration')->willReturn(new AuditConfiguration());
        $reader->method('getConnection')->willReturn($connection);
        $reader->method('find')->willReturnCallback(static function (string $class, array $keys, int $revision) use ($entitiesById): PageLocalization {
            static::assertSame(PageLocalization::class, $class);
            static::assertSame(42, $revision);

            return $entitiesById[$keys['id']];
        });

        $metadata = new ClassMetadata(PageLocalization::class);
        $metadata->setPrimaryTable(['name' => 'localization']);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);

        return new AuditedCollection($reader, PageLocalization::class, $metadata, ['indexBy' => 'locale'], ['page_id' => 1], 42);
    }
}
