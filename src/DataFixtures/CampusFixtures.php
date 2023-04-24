<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public const SAINT_HERBLAIN_REFERENCE = 'saint-herblain-campus';
    public const CHARTRES_DE_BRETAGNE_REFERENCE = 'chartres-de-bretagne-campus';
    public const LA_ROCHE_SUR_YON_REFERENCE = 'la-roche-sur-yon-campus';
    public const ONLINE_CAMPUS_REFERENCE = 'online-campus';

    public function load(ObjectManager $manager): void
    {
        $saintHerblain = (new Campus())
            ->setName('Saint-Herblain');
        $manager->persist($saintHerblain);

        $chartresDeBretagne = (new Campus())
            ->setName('Chartres-de-Bretagne');
        $manager->persist($chartresDeBretagne);

        $laRocheSurYon = (new Campus())
            ->setName('La Roche-Sur-Yon');
        $manager->persist($laRocheSurYon);

        $onlineCampus = (new Campus())
            ->setName('Online Campus');
        $manager->persist($onlineCampus);

        $manager->flush();

        $this->addReference(self::SAINT_HERBLAIN_REFERENCE, $saintHerblain);
        $this->addReference(self::CHARTRES_DE_BRETAGNE_REFERENCE, $chartresDeBretagne);
        $this->addReference(self::LA_ROCHE_SUR_YON_REFERENCE, $laRocheSurYon);
        $this->addReference(self::ONLINE_CAMPUS_REFERENCE, $onlineCampus);
    }
}
