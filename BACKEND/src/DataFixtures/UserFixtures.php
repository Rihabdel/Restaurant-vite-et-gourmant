<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}
    /** * @throws Exception */

    public function load(ObjectManager $manager): void
    { // creation d'un utilisateur//
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setFirstName('User' . $i);
            $user->setLastName('Lastname' . $i);
            $user->setEmail('user' . $i . '@mail.com');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password' . $i));
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new DateTimeImmutable());
            $manager->persist($user);
            $this->addReference('User' . $i, $user);
        }
        $manager->flush();
    }
}
