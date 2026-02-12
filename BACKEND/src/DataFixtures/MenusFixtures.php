<?php

namespace App\DataFixtures;

use App\Entity\Menus;
use App\Enum\Theme;
use App\Enum\Diet;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

class MenusFixtures extends Fixture
{
    public function __construct()
    {
        /** * @throws Exception */
    }
    public function load(ObjectManager $manager): void
    {
        //creation de 10 menus//
        for ($i = 0; $i < 10; $i++) {
            $menu = new Menus();
            $menu->setTitle('Menu' . $i);
            $menu->setDescriptionMenu('Description du menu ' . $i);
            $menu->setMinPeople(1 + $i);
            $menu->setStock(100 - 10 * $i);
            // theme en enum
            $menu->setThemeMenu(Theme::from('noel'));
            $menu->setDietMenu(Diet::from('vegetarien'));
            $menu->setOrderBefore(20 + $i);
            $menu->setConditions('commander ce menu avant' . $menu->getOrderBefore() . 'heures');
            $menu->setPrice(9.99 + $i);
            $menu->setCreatedAt(new DateTimeImmutable());
            $manager->persist($menu);
            $this->addReference('Menu' . $i, $menu);
        }
        $manager->flush();
    }
}
