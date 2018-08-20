<?php

namespace Aviron\SortieBundle\Repository;

/**
 * TypeBateauRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TypeBateauRepository extends \Doctrine\ORM\EntityRepository
{
    public function getListeTypesBateau()
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->orderBy('t.nom');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
