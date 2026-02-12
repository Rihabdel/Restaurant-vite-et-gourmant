<?php

namespace App\DataFixtures;

use App\Entity\Dishes;
use App\Enum\CategoryDishes;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

class DishesFixtures extends Fixture
{
    public function __construct()
    {
        /** * @throws Exception */
    }
    public function load(ObjectManager $manager): void
    {
        //creation de 10 plats//
        for ($i = 0; $i < 10; $i++) {
            $dish = new Dishes();
            $dish->setName('Plat' . $i);
            $dish->setDescription('Description du plat ' . $i);
            $dish->setCategory(CategoryDishes::from('plat'));
            $dish->setPrice(9.99 + $i);
            $dish->setCreatedAt(new DateTimeImmutable());
            $manager->persist($dish);
            $this->addReference('Dish' . $i, $dish);
        }
        $manager->flush();
    }
}
