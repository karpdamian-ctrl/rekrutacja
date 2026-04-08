<?php

declare(strict_types=1);

namespace App\Home\Controller;

use App\Likes\LikeRepositoryInterface;
use App\Photo\Repository\PhotoRepository;
use App\Shared\Controller\AppController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AppController
{
    /**
     * @Route("/", name="home")
     */
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PhotoRepository $photoRepository,
        LikeRepositoryInterface $likeRepository
    ): Response {
        $photos = $photoRepository->findAllWithUsers();

        $currentUser = $this->resolveCurrentUser($request, $em, true);
        $userLikes = [];

        if ($currentUser) {
            foreach ($photos as $photo) {
                $userLikes[$photo->getId()] = $likeRepository->hasUserLikedPhoto($currentUser, $photo);
            }
        }

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'currentUser' => $currentUser,
            'userLikes' => $userLikes,
        ]);
    }
}
