<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\Theme;
use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Create roles
        $roleAdmin = new Role();
        $roleAdmin->setName('admin');
        $manager->persist($roleAdmin);

        $roleClient = new Role();
        $roleClient->setName('client');
        $manager->persist($roleClient);

        $manager->flush();

        // 2. Create admin user
        $admin = new User();
        $admin->setEmail('admin@knowledge.com');
        $admin->setPassword(password_hash('Admin123!', PASSWORD_BCRYPT));
        $admin->setIsActive(true);
        $admin->setIsVerified(true);
        $admin->setRole($roleAdmin);
        $manager->persist($admin);

        $manager->flush();

        // 3. Create themes and courses
        $themesData = [
            'Musique' => [
                ['name' => 'Initiation à la guitare', 'price' => 50.00],
                ['name' => 'Initiation au piano', 'price' => 50.00],
            ],
            'Informatique' => [
                ['name' => 'Initiation au développement web', 'price' => 60.00],
            ],
            'Jardinage' => [
                ['name' => 'Initiation au jardinage', 'price' => 30.00],
            ],
            'Cuisine' => [
                ['name' => 'Initiation à la cuisine', 'price' => 44.00],
                ['name' => 'Initiation à l\'art du dressage culinaire', 'price' => 48.00],
            ],
        ];

        $lessonsData = [
            'Initiation à la guitare' => [
                ['title' => 'Découverte de l\'instrument', 'price' => 26.00, 'order' => 1],
                ['title' => 'Les accords et les gammes', 'price' => 26.00, 'order' => 2],
            ],
            'Initiation au piano' => [
                ['title' => 'Découverte de l\'instrument', 'price' => 26.00, 'order' => 1],
                ['title' => 'Les accords et les gammes', 'price' => 26.00, 'order' => 2],
            ],
            'Initiation au développement web' => [
                ['title' => 'Les langages Html et CSS', 'price' => 32.00, 'order' => 1],
                ['title' => 'Dynamiser votre site avec Javascript', 'price' => 32.00, 'order' => 2],
            ],
            'Initiation au jardinage' => [
                ['title' => 'Les outils du jardinier', 'price' => 16.00, 'order' => 1],
                ['title' => 'Jardiner avec la lune', 'price' => 16.00, 'order' => 2],
            ],
            'Initiation à la cuisine' => [
                ['title' => 'Les modes de cuisson', 'price' => 23.00, 'order' => 1],
                ['title' => 'Les saveurs', 'price' => 23.00, 'order' => 2],
            ],
            'Initiation à l\'art du dressage culinaire' => [
                ['title' => 'Mettre en œuvre le style dans l\'assiette', 'price' => 26.00, 'order' => 1],
                ['title' => 'Harmoniser un repas à quatre plats', 'price' => 26.00, 'order' => 2],
            ],
        ];

        foreach ($themesData as $themeName => $courses) {
            $theme = new Theme();
            $theme->setName($themeName);
            $theme->setDescription('Thème de formation en ' . $themeName);
            $manager->persist($theme);

            foreach ($courses as $courseData) {
                $course = new Course();
                $course->setTitle($courseData['name']);
                $course->setPrice((string) $courseData['price']);
                $course->setTheme($theme);
                $manager->persist($course);

                if (isset($lessonsData[$courseData['name']])) {
                    foreach ($lessonsData[$courseData['name']] as $lessonData) {
                        $lesson = new Lesson();
                        $lesson->setTitle($lessonData['title']);
                        $lesson->setPrice((string) $lessonData['price']);
                        $lesson->setOrderNumber($lessonData['order']);
                        $lesson->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
                        $lesson->setCourse($course);
                        $lesson->setVideoUrl(null);
                        $manager->persist($lesson);
                    }
                }
            }
        }

        $manager->flush();
    }
}