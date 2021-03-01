<?php
declare(strict_types=1);

namespace Markup\JobQueueBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry as Doctrine;

trait DoctrineOrmAwareRepositoryTrait
{
    /**
     * @var Doctrine
     */
    private $doctrine;

    /**
     * @var string
     */
    private $entity;

    private function getEntityRepository(): EntityRepository
    {
        $this->ensureDatabaseConnectionIsOpen();

        $repository = $this->doctrine->getRepository($this->entity);

        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid repository for entity %s', $this->entity));
    }

    protected function getEntityManager(): EntityManager
    {
        $manager = $this->doctrine->getManager();

        if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
            $manager = $this->doctrine->resetManager();
        }

        if (!$manager instanceof EntityManager) {
            throw new \RuntimeException('Doctrine returned an invalid manager');
        }

        return $manager;
    }

    private function ensureDatabaseConnectionIsOpen(): void
    {
        $manager = $this->doctrine->getManager();

        if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
            $this->doctrine->resetManager();
        }

        return;
    }

    /**
     * @param object $entity
     * @throws \Doctrine\ORM\ORMException
     */
    protected function persist($entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    protected function flush($entity): void
    {
        $this->getEntityManager()->flush($entity);
    }

    /**
     * @param object $entity
     */
    protected function remove($entity): void
    {
        $this->getEntityManager()->remove($entity);
    }
}
