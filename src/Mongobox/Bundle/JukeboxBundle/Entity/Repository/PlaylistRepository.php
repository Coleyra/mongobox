<?php

namespace Mongobox\Bundle\JukeboxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Mongobox\Bundle\JukeboxBundle\Entity\Playlist;

/**
 * PlaylistRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PlaylistRepository extends EntityRepository
{
    public function next($max, $group)
    {
        $q = $this
                ->createQueryBuilder('p')
                ->select('p')
				->where('p.group = :group')
				->andWhere('p.current = 0')
                ->orderBy('p.random', 'ASC')
                ->addOrderBy('p.date', 'ASC')
                ->setMaxResults($max)
				->setParameters(array('group' => $group))
                ->getQuery()
        ;

        if($max == 1) $result = $q->getOneOrNullResult ();
        else $result = $q->getResult();
        return $result;
    }

    public function generate($group)
    {
        $continue= true;
        while (count($this->findBy(array('group' => $group))) < 10 AND $continue) {
            $random = $this->_em->getRepository('MongoboxJukeboxBundle:VideoGroup')->random($group);
            if(is_null($random)) $continue = false;
            else {
                $playlist_add = new Playlist();
                $playlist_add->setVideoGroup($random);
                $playlist_add->setGroup($group);
                $playlist_add->setRandom(1);
                $playlist_add->setDate(new \Datetime());
                $this->_em->persist($playlist_add);
                $this->_em->flush();
            }
        }
    }
}