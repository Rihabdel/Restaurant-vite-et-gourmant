<?php

namespace App\DataFixtures;

use App\Entity\Allergens;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

class AllergensFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    { //creation de 10 allergènes/
        for ($i = 0; $i < 10; $i++) {
            $allergen = new Allergens();
            $allergen->setName('Allergène' . $i);
            $allergen->setDescription('Description de l\'allergène ' . $i);
            $allergen->setCreatedAt(new DateTimeImmutable());
            $manager->persist($allergen);
            $this->addReference('Allergen' . $i, $allergen);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [
            DishesFixtures::class,
        ];
    }
}
