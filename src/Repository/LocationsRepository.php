<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 *
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function add(Location $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Location $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findCities(): array {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DISTINCT city FROM Location ORDER BY city;';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

    public function filterCityCharger($city, $charger): array {
        $sql = 'SELECT l FROM App\Entity\Location l INNER JOIN App\Entity\Station s WHERE l.city = ?1 AND s.type = ?2 AND s.Location = l';
        return $this->getEntityManager()->createQuery($sql)->setParameter(1, $city)->setParameter(2, $charger)->getResult();
    }

    public function filterCharger($charger): array {
        $sql = 'SELECT l FROM App\Entity\Location l INNER JOIN App\Entity\Station s WHERE s.type = ?1 AND s.Location = l';
        return $this->getEntityManager()->createQuery($sql)->setParameter(1, $charger)->getResult();
    }
    /**
     * @return Location[] Returns an array of LocationFixtures objects
     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//           ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }*/

//    public function findOneById($value): ?Location
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.id = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
