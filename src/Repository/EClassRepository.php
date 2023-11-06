<?php

namespace App\Repository;

use App\Entity\ArticleToBrand;
use App\Entity\Brand;
use App\Entity\EClass;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EClassRepository
 * @method EClass|null findOneBy(array $criteria, array $orderBy = null)
 */
class EClassRepository extends ServiceEntityRepository {
    private TranslatorInterface $translator;

    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator
    ) {
        parent::__construct($registry, EClass::class);

        $this->translator = $translator;
    }

    /**
     * @param int $eClassCode
     * @return array
     */
    public function findChildrenFrom(int $eClassCode): array {
        $eClass = $this->findOneBy(['code' => $eClassCode]);
        if ($eClass->getLevelModuloQuotient() === 1) {
            return [];
        }

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('e')->from(EClass::class, 'e');
        $queryBuilder->where('(e.code != :eClassCode)');
        $queryBuilder->andWhere('(e.version = :version)');
        $queryBuilder->andWhere('((MOD((ROUND(e.code / :quotient,0) * :quotient),100000000)) = (MOD((:eClassCode),100000000)) )');
        $queryBuilder->andWhere('(MOD(e.code, (:quotient / 100) ) =0)');
        $queryBuilder->setParameter('eClassCode', $eClass->getCode());
        $queryBuilder->setParameter('version', $eClass->getVersion());
        $queryBuilder->setParameter('quotient', $eClass->getLevelModuloQuotient());
        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * @param int $brandId
     * @return int
     */
    public function getArticleCountByBrand(int $brandId) :int {
        $qb = $this->_em->createQueryBuilder();
        $result = $qb->select('b.id AS ids')
            ->addSelect($qb->expr()->countDistinct('a2b.id') . ' AS articleCount')
            ->from(Brand::class, 'b')
            ->leftJoin(
                ArticleToBrand::class,
                'a2b',
                Join::WITH,
                'a2b.brandId = b.id'
            )
            ->where('b.id = :brandId')
            ->andWhere('b.level = 3')
            ->groupBy('b.id')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->execute();

        return array_reduce(array_column($result, 'articleCount'), fn ($carry, $item) => $carry + $item) ?? 0;
    }
}
