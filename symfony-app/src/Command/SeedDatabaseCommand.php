<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AuthToken;
use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Seed the database with sample users and photos',
)]
class SeedDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Seeding database with sample data');

        // Sample users data
        $usersData = [
            [
                'username' => 'nature_lover',
                'email' => 'nature@example.com',
                'name' => 'Emma',
                'lastName' => 'Wilson',
                'age' => 28,
                'bio' => 'Passionate about wildlife and nature photography. Exploring the world one photo at a time.',
            ],
            [
                'username' => 'wildlife_pro',
                'email' => 'wildlife@example.com',
                'name' => 'James',
                'lastName' => 'Anderson',
                'age' => 35,
                'bio' => 'Professional wildlife photographer with 10 years of experience.',
            ],
            [
                'username' => 'landscape_dreams',
                'email' => 'landscape@example.com',
                'name' => 'Sofia',
                'lastName' => 'Martinez',
                'age' => 31,
                'bio' => 'Capturing the beauty of landscapes around the globe.',
            ],
            [
                'username' => 'animal_eyes',
                'email' => 'animals@example.com',
                'name' => 'Michael',
                'lastName' => 'Brown',
                'age' => 42,
                'bio' => 'Specializing in close-up animal photography.',
            ],
        ];

        $users = [];
        foreach ($usersData as $userData) {
            $user = new User();
            $user->setUsername($userData['username'])
                ->setEmail($userData['email'])
                ->setName($userData['name'])
                ->setLastName($userData['lastName'])
                ->setAge($userData['age'])
                ->setBio($userData['bio']);

            $this->entityManager->persist($user);
            $users[] = $user;

            $io->text("Created user: {$userData['username']}");
        }

        $this->entityManager->flush();

        // Create auth tokens for each user
        foreach ($users as $user) {
            $token = bin2hex(random_bytes(32));
            $authToken = new AuthToken();
            $authToken->setToken($token)
                ->setUser($user);

            $this->entityManager->persist($authToken);

            $io->text("Created auth token for {$user->getUsername()}: {$token}");
        }

        $this->entityManager->flush();

        // Sample photos data with picsum URLs (nature/animals themed)
        $photosData = [
            [
                'imageUrl' => 'https://picsum.photos/seed/forest1/800/600',
                'location' => 'Olympic National Park, Washington',
                'description' => 'Misty morning in the ancient forest. The towering trees create a magical atmosphere.',
                'camera' => 'Canon EOS R5',
                'takenAt' => '2024-03-15 07:30:00',
                'userIndex' => 0,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/mountain1/800/600',
                'location' => 'Swiss Alps',
                'description' => 'Breathtaking view of snow-capped peaks at sunrise.',
                'camera' => 'Sony A7R IV',
                'takenAt' => '2024-01-22 06:15:00',
                'userIndex' => 2,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/deer1/800/600',
                'location' => 'Yellowstone National Park',
                'description' => 'A majestic deer in its natural habitat, grazing peacefully.',
                'camera' => 'Nikon D850',
                'takenAt' => '2024-05-10 17:45:00',
                'userIndex' => 1,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/ocean1/800/600',
                'location' => 'Big Sur, California',
                'description' => 'Where the ocean meets the rugged coastline. Nature at its finest.',
                'camera' => 'Fujifilm X-T4',
                'takenAt' => '2024-04-08 18:20:00',
                'userIndex' => 2,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/bird1/800/600',
                'location' => 'Amazon Rainforest, Brazil',
                'description' => 'Vibrant tropical bird perched on a branch, showcasing nature\'s colors.',
                'camera' => 'Canon EOS R6',
                'takenAt' => '2024-02-14 09:30:00',
                'userIndex' => 3,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/lake1/800/600',
                'location' => 'Lake Louise, Canada',
                'description' => 'Crystal clear mountain lake reflecting the surrounding peaks.',
                'camera' => 'Sony A7 III',
                'takenAt' => '2024-06-25 11:00:00',
                'userIndex' => 0,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/fox1/800/600',
                'location' => 'Scottish Highlands',
                'description' => 'A curious fox exploring the moorlands at dawn.',
                'camera' => 'Nikon Z7 II',
                'takenAt' => '2024-07-03 05:45:00',
                'userIndex' => 3,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/waterfall1/800/600',
                'location' => 'Iceland',
                'description' => 'Powerful waterfall cascading down volcanic rocks.',
                'camera' => 'Canon EOS 5D Mark IV',
                'takenAt' => '2024-08-19 14:30:00',
                'userIndex' => 2,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/bear1/800/600',
                'location' => 'Alaska',
                'description' => 'Brown bear fishing for salmon in a pristine river.',
                'camera' => 'Nikon D6',
                'takenAt' => '2024-09-05 16:00:00',
                'userIndex' => 1,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/sunset1/800/600',
                'location' => 'Serengeti, Tanzania',
                'description' => 'Golden hour on the African savanna. Nature\'s perfect lighting.',
                'camera' => 'Sony A1',
                'takenAt' => '2024-10-12 19:15:00',
                'userIndex' => 0,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/wolf1/800/600',
                'location' => 'Canadian Rockies',
                'description' => 'A lone wolf surveying its territory from a rocky outcrop.',
                'camera' => 'Canon EOS-1D X Mark III',
                'takenAt' => '2024-11-20 08:30:00',
                'userIndex' => 1,
            ],
            [
                'imageUrl' => 'https://picsum.photos/seed/meadow1/800/600',
                'location' => 'Alps, Austria',
                'description' => 'Wildflower meadow in full bloom during spring.',
                'camera' => 'Fujifilm GFX 100',
                'takenAt' => '2024-05-18 13:20:00',
                'userIndex' => 2,
            ],
        ];

        foreach ($photosData as $photoData) {
            $photo = new Photo();
            $photo->setImageUrl($photoData['imageUrl'])
                ->setLocation($photoData['location'])
                ->setDescription($photoData['description'])
                ->setCamera($photoData['camera'])
                ->setTakenAt(new \DateTimeImmutable($photoData['takenAt']))
                ->setUser($users[$photoData['userIndex']]);

            $this->entityManager->persist($photo);

            $io->text("Created photo: {$photoData['description']}");
        }

        $this->entityManager->flush();

        $io->success('Database seeded successfully!');
        $io->info(sprintf('Created %d users, %d auth tokens, and %d photos', count($usersData), count($usersData), count($photosData)));

        return Command::SUCCESS;
    }
}
