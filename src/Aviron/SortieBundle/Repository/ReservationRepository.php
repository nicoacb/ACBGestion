<?php

namespace Aviron\SortieBundle\Repository;

/**
 * ReservationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReservationRepository extends \Doctrine\ORM\EntityRepository
{
    public function getListeEntrainements()
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->orderBy('e.datedebut', 'DESC')
            ->addOrderBy('e.heuredebut');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getListeEntrainementsAVenir()
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->where("e.datedebut >= :aujourdhui")
            ->orderBy('e.datedebut', 'DESC')
            ->addOrderBy('e.heuredebut')
            ->setParameter('aujourdhui', date("Y-m-d", time()));

        return $qb
            ->getQuery()
            ->getResult();
    }
}
