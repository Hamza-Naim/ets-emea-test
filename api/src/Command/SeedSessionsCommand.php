<?php

namespace App\Command;

use App\Document\TestSession;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-sessions', description: 'Seed test sessions in MongoDB')]
class SeedSessionsCommand extends Command
{
    public function __construct(private DocumentManager $dm)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $languages = ['English', 'French', 'Spanish', 'German', 'Italian', 'Portuguese', 'Mandarin', 'Arabic'];
        $locations = ['Paris', 'London', 'Madrid', 'Berlin', 'Rome', 'Lisbon', 'Casablanca', 'Brussels'];
        $times = ['09:00', '10:30', '14:00', '15:30', '17:00'];

        $count = 0;
        for ($i = 1; $i <= 15; $i++) {
            $session = new TestSession();
            $session->setLanguage($languages[array_rand($languages)]);
            $session->setDate(new \DateTimeImmutable("+$i days"));
            $session->setTime($times[array_rand($times)]);
            $session->setLocation($locations[array_rand($locations)]);
            $totalSeats = rand(5, 20);
            $session->setTotalSeats($totalSeats);
            $session->setAvailableSeats($totalSeats);

            $this->dm->persist($session);
            $count++;
        }

        $this->dm->flush();
        $io->success("$count sessions created!");

        return Command::SUCCESS;
    }
}