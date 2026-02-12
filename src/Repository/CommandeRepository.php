<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Retourne les commandes qui contiennent au moins un produit
     * appartenant au vendeur donné.
     *
     * @return Commande[]
     */
    public function findForVendeur(User $vendeur): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.items', 'i')
            ->innerJoin('i.produit', 'p')
            ->andWhere('p.vendeur = :vendeur')
            ->setParameter('vendeur', $vendeur)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
