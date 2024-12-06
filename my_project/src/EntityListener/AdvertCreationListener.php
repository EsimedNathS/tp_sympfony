<?php
namespace App\EntityListener;

use App\Entity\Advert;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Advert::class)]
Class AdvertCreationListener {

    public function prePersist(Advert $advert): void
    {
        if ($advert->getCreatedAt() == null) {
            $advert->setCreatedAt(new \DateTime());
        }
    }


}