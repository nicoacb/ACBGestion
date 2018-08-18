<?php

namespace Aviron\SortieBundle\Repository;

/**
 * BateauRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BateauRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNbRameursQueryBuilder($nbRameurs)
    {
        if ($nbRameurs != 0) {
            return $this
                ->createQueryBuilder('b')
                ->join('b.type', 'type')
                ->where('type.nbplacerameurs = :nbRameurs')
                ->andWhere('b.datesupp is null')
                ->orderBy('b.nom')
                ->setParameter('nbRameurs', $nbRameurs);
        } else {
            return $this
                ->createQueryBuilder('b')
                ->where('b.datesupp is null')
                ->orderBy('b.nom');
        }
    }

    public function getListeBateaux()
    {
        $qb = $this
            ->createQueryBuilder('b')
            ->join('b.type', 'type')
            ->where('b.datesupp is null')
            ->orderBy('type.nom')
            ->addOrderBy('b.nom');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
