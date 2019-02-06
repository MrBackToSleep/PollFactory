<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PollRepository;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(PollRepository $pollRepository)
    {
        return $this->render('home/index.html.twig', [
            'polls' => $pollRepository->findBy(
                [],
                ['creationDate' => 'DESC'],
                8
            )
        ]);
    }
}
