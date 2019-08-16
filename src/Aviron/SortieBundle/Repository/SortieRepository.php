<?php

namespace Aviron\SortieBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * SortieRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SortieRepository extends \Doctrine\ORM\EntityRepository
{
    public function getSortiesEnCours()
    {
        return $this->createQueryBuilder('s')
                    ->where('s.hretour IS NULL')
                    ->andWhere('s.datesupp IS NULL')
                    ->orderBy('s.date', 'DESC')
                    ->addOrderBy('s.hdepart')
                    ->getQuery()
                    ->getResult();
    }

    public function getSortiesTerminees($page, $nbParPage)
    {
        $query = $this->createQueryBuilder('s')
                    ->where('s.hretour IS NOT NULL')
                    ->andWhere('s.datesupp IS NULL')
                    ->orderBy('s.date', 'DESC')
                    ->addOrderBy('s.hretour', 'DESC')
                    ->setFirstResult(($page-1)*$nbParPage)
                    ->setMaxResults($nbParPage);

        return new Paginator($query, true);
    }

    public function getSortiesTermineesStatistiques($annee, $mois)
    {
        $query =  $this->createQueryBuilder('s')
                    ->where('s.hretour IS NOT NULL')
                    ->andWhere('s.datesupp IS NULL');

        if ($annee > 0)
        {
            $query = $query->andWhere('YEAR(s.date) = :annee')
                        ->setParameter('annee', $annee);
            if ($mois > 0)
            {
                $query = $query->andWhere('MONTH(s.date) = :mois')
                ->setParameter('mois', $mois);
            }
        }            

        return $query->orderBy('s.date', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    public function getSortiesMembre($idMembre)
    {
        return $this->createQueryBuilder('s')
                    ->join('s.athletes', 'a')
                    ->where('a.id = :idMembre')
                    ->andWhere('s.hretour IS NOT NULL')
                    ->andWhere('s.datesupp IS NULL')
                    ->orderBy('s.date', 'DESC')
                    ->setParameter('idMembre', $idMembre)
                    ->getQuery()
                    ->getResult();
    }

    public function getSortiesBateau($idBateau)
    {
        return $this->createQueryBuilder('s')
                    ->where('s.bateau = :idBateau')
                    ->andWhere('s.hretour IS NOT NULL')
                    ->andWhere('s.datesupp IS NULL')
                    ->orderBy('s.date', 'DESC')
                    ->setParameter('idBateau', $idBateau)
                    ->getQuery()
                    ->getResult();
    }
}
