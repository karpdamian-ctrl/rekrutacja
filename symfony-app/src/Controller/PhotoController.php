<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\LikeRepository;
use App\Likes\LikeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhotoController extends AbstractController
{
    #[Route('/photo/{id}/like', name: 'photo_like')]
    public function like($id, Request $request, EntityManagerInterface $em, ManagerRegistry $managerRegistry): Response
    {
        $likeRepository = new LikeRepository($managerRegistry);
        $likeService = new LikeService($likeRepository);

        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to like photos.');
            return $this->redirectToRoute('home');
        }

        $user = $em->getRepository(User::class)->find($userId);
        $photo = $em->getRepository(Photo::class)->find($id);

        $likeRepository->setUser($user);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

        if ($likeRepository->hasUserLikedPhoto($photo)) {
            $likeRepository->unlikePhoto($photo);
            $this->addFlash('info', 'Photo unliked!');
        } else {
            $likeService->execute($photo);
            $this->addFlash('success', 'Photo liked!');
        }

        return $this->redirectToRoute('home');
    }
}
