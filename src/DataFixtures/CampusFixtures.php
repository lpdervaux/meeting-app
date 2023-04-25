<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public const SAINT_HERBLAIN = 'campus_saint-herblain';
    public const CHARTRES_DE_BRETAGNE = 'campus_chartres-de-bretagne';
    public const LA_ROCHE_SUR_YON = 'campus_la-roche-sur-yon';
    public const ONLINE = 'campus_online';

    public function load(ObjectManager $manager): void
    {
        $saintHerblain = (new Campus())
            ->setName('Saint-Herblain');
        $manager->persist($saintHerblain);
        $this->addReference(self::SAINT_HERBLAIN, $saintHerblain);

        $chartresDeBretagne = (new Campus())
            ->setName('Chartres-de-Bretagne');
        $manager->persist($chartresDeBretagne);
        $this->addReference(self::CHARTRES_DE_BRETAGNE, $chartresDeBretagne);

        $laRocheSurYon = (new Campus())
            ->setName('La Roche-Sur-Yon');
        $manager->persist($laRocheSurYon);
        $this->addReference(self::LA_ROCHE_SUR_YON, $laRocheSurYon);

        $onlineCampus = (new Campus())
            ->setName('Online Campus');
        $manager->persist($onlineCampus);
        $this->addReference(self::ONLINE, $onlineCampus);

        $manager->flush();
    }
}
