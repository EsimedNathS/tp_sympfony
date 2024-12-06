<?php
namespace App\EntityListener;

use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: AdminUser::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: AdminUser::class)]
class AdminUserPasswordListener
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    public function prePersist(AdminUser $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword()
                )
            );
            $user->setPlainPassword(null); // Clear the plain password
        }
    }

    public function preUpdate(AdminUser $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword()
                )
            );
            $user->setPlainPassword(null); // Clear the plain password
        }
    }
}


