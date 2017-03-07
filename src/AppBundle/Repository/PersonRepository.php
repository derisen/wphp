<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Person;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * PersonRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PersonRepository extends EntityRepository {

    /**
     * Return the next firm by ID.
     * 
     * @param Person $person
     * @return Person|Null
     */
    public function next(Person $person) {
        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.id > :id');
        $qb->setParameter('id', $person->getId());
        $qb->addOrderBy('e.id', 'ASC');
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Return the next firm by ID.
     * 
     * @param Person $person
     * @return Person|Null
     */
    public function previous(Person $person) {
        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.id < :id');
        $qb->setParameter('id', $person->getId());
        $qb->addOrderBy('e.id', 'DESC');
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function buildSearchQuery($data) {
        $qb = $this->createQueryBuilder('e');
        if (isset($data['name']) && $data['name']) {
            $qb->andWhere("MATCH_AGAINST (e.lastName, e.firstName, e.title, :name 'IN BOOLEAN MODE') > 0");
            $qb->setParameter('name', $data['name']);
        }
        if (isset($data['gender']) && $data['gender']) {
            $genders = [];
            if (in_array('M', $data['gender'])) {
                $genders[] = 'M';
            }
            if (in_array('F', $data['gender'])) {
                $genders[] = 'F';
            }
            if (in_array('U', $data['gender'])) {
                $genders[] = '';
            }
            $qb->andWhere('e.gender in (:genders)');
            $qb->setParameter('genders', $genders);
        }

        if (isset($data['dob']) && $data['dob']) {
            $m = array();
            if (preg_match('/^\s*[0-9]{4}\s*$/', $data['dob'])) {
                $qb->andWhere('YEAR(e.dob) = :yearb');
                $qb->setParameter('yearb', $data['dob']);
            } else if (preg_match('/^\s*(\*|[0-9]{4})\s*-\s*(\*|[0-9]{4})\s*$/', $data['dob'], $m)) {
                $from = ($m[1] === '*' ? -1 : $m[1]);
                $to = ($m[2] === '*' ? 9999 : $m[2]);
                $qb->andWhere(':fromb <= YEAR(e.dob) AND YEAR(e.dob) <= :tob');
                $qb->setParameter('fromb', $from);
                $qb->setParameter('tob', $to);
            }
        }

        if (isset($data['dod']) && $data['dod']) {
            $m = array();
            if (preg_match('/^\s*[0-9]{4}\s*$/', $data['dod'])) {
                $qb->andWhere('e.dod = :yeard');
                $qb->setParameter('yeard', $data['dod']);
            } else if (preg_match('/^\s*(\*|[0-9]{4})\s*-\s*(\*|[0-9]{4})\s*$/', $data['dod'], $m)) {
                $from = ($m[1] === '*' ? -1 : $m[1]);
                $to = ($m[2] === '*' ? 9999 : $m[2]);
                $qb->andWhere(':fromd <= e.dod AND e.dod <= :tod');
                $qb->setParameter('fromd', $from);
                $qb->setParameter('tod', $to);
            }
        }

        if (isset($data['birthplace']) && $data['birthplace']) {
            $qb->innerJoin('e.cityOfBirth', 'b');
            $qb->andWhere('MATCH_AGAINST(b.alternatenames, b.name, :bpname) > 0');
            $qb->setParameter('bpname', $data['birthplace']);
        }

        if (isset($data['deathplace']) && $data['deathplace']) {
            $qb->innerJoin('e.cityOfDeath', 'd');
            $qb->andWhere('MATCH_AGAINST(d.alternatenames, d.name, :dpname) > 0');
            $qb->setParameter('dpname', $data['deathplace']);
        }

        if (isset($data['title_filter']) && count($data['title_filter']) > 0) {
            foreach ($data['title_filter'] as $idx => $filter) {
                $trAlias = 'tr_' . $idx;
                $tAlias = 't_' . $idx;
                $qb->innerJoin('e.titleRoles', $trAlias)->innerJoin("{$trAlias}.title", $tAlias);
                if (isset($filter['title']) && $filter['title']) {
                    $qb->andWhere("MATCH_AGAINST({$tAlias}.title, :{$tAlias}_title 'IN BOOLEAN MODE') > 0");
                    $qb->setParameter("{$tAlias}_title", $filter['title']);
                }
            }
            
            if(isset($filter['person_role']) && $filter['person_role']) {
                $qb->andWhere("{$trAlias}.role IN (:{$trAlias}_roles)");
                $qb->setParameter("{$trAlias}_roles", $filter['person_role']);
            }

            if (isset($filter['pubdate']) && $filter['pubdate']) {
                $m = array();
                if (preg_match('/^\s*[0-9]{4}\s*$/', $filter['pubdate'])) {
                    $qb->andWhere("YEAR(STRTODATE({$tAlias}.pubdate, '%Y')) = :{$tAlias}_year");
                    $qb->setParameter("{$tAlias}_year", $filter['pubdate']);
                } else if (preg_match('/^\s*(\*|[0-9]{4})\s*-\s*(\*|[0-9]{4})\s*$/', $filter['pubdate'], $m)) {
                    $from = ($m[1] === '*' ? -1 : $m[1]);
                    $to = ($m[2] === '*' ? 9999 : $m[2]);
                    $qb->andWhere(":{$tAlias}_from <= YEAR(STRTODATE({$tAlias}.pubdate, '%Y')) AND YEAR(STRTODATE({$tAlias}.pubdate, '%Y')) <= :{$tAlias}_to");
                    $qb->setParameter("{$tAlias}_from", $from);
                    $qb->setParameter("{$tAlias}_to", $to);
                }
            }
            
            if(isset($filter['genre']) && count($filter['genre']) > 0) {
                $qb->andWhere("{$tAlias}.genre in (:{$tAlias}_genres)");
                $qb->setParameter("{$tAlias}_genres", $filter['genre']);
            }
            
            if(isset($filter['location']) && $filter['location']) {
                $gAlias = 'g_' . $idx;
                $qb->innerJoin("{$tAlias}.locationOfPrinting", $gAlias);
                $qb->andWhere("MATCH_AGAINST({$gAlias}.alternatenames, {$gAlias}.name, :{$gAlias}_location 'IN BOOLEAN MODE') > 0");
                $qb->setParameter("{$gAlias}_location", $filter['location']);
            }
        }

        if (isset($data['orderby']) && $data['orderby']) {
            $dir = 'ASC';
            if (isset($data['orderdir']) && preg_match('/^(?:asc|desc)$/i', $data['orderdir'])) {
                $dir = $data['orderdir'];
            }
            switch ($data['orderby']) {
                case 'firstname':
                    $qb->orderBy('e.firstName', $dir);
                    break;
                case 'dob':
                    $qb->orderBy('date(e.dob)', $dir);
                    break;
                case 'dod':
                    $qb->orderBy('date(e.dod)', $dir);
                    break;
                case 'lastname':
                default:
                    $qb->orderBy('e.lastName', $dir);
                    break;
            }
        }

        return $qb->getQuery();
    }

    public function fulltextQuery($q) {
        $qb = $this->createQueryBuilder('e');
        $qb->addSelect("MATCH_AGAINST (e.lastName, e.firstName, e.dob, e.dod, :q 'IN BOOLEAN MODE') as score");
        $qb->add('where', "MATCH_AGAINST (e.lastName, e.firstName, e.dob, e.dod, :q 'IN BOOLEAN MODE') > 0.5");
        $qb->orderBy('score', 'desc');
        $qb->setParameter('q', $q);
        return $qb->getQuery();
    }

    public function random($limit) {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('RAND()');
        $qb->setMaxResults($limit);
        return $qb->getQuery()->execute();
    }

}
