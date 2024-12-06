<?php
namespace App\EntityListener;

use App\Entity\Picture;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Picture::class)]
Class PictureCreationListener {

    public function prePersist(Picture $picture): void
    {
        if ($picture->getCreatedAt() == null) {
            $picture->setCreatedAt(new \DateTime());
        }
    }
}