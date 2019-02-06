<?php

namespace App\Controller;

use App\Entity\PollOption;
use App\Form\PollOptionType;
use App\Repository\PollOptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Poll;

/**
 * @Route("/poll_options")
 */

class PollOptionController extends AbstractController
{
    /**
     * @Route("/list/{poll}", name="poll_option_index", methods={"GET"})
     */
    public function index(Poll $poll, PollOptionRepository $pollOptionRepository): Response
    {
        if($poll->getUser() == $this->getUser()){
            return $this->render('poll_option/index.html.twig', [
                'poll_options' => $pollOptionRepository->findBy(['poll' => $poll->getId()]),
                'poll' => $poll
            ]);
        }else{
            return $this->redirectToRoute('poll_index');
        }
    }

    /**
     * @Route("/{poll}/new", name="poll_option_new", methods={"GET","POST"})
     */
    public function new(Poll $poll, Request $request): Response
    {
        $pollOption = new PollOption();
        $pollOption->setPoll($poll);
        $form = $this->createForm(PollOptionType::class, $pollOption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($pollOption);
            $entityManager->flush();

            return $this->redirectToRoute('poll_option_index', [
                'poll' => $poll->getId()
            ]);
        }
        if($poll->getUser() == $this->getUser()){
            return $this->render('poll_option/new.html.twig', [
                'poll' => $poll,
                'poll_option' => $pollOption,
                'form' => $form->createView(),
            ]);
        }else{
            return $this->redirectToRoute('poll_index');
        }
    }

    /**
     * @Route("/edit/{id}", name="poll_option_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PollOption $pollOption): Response
    {
        $form = $this->createForm(PollOptionType::class, $pollOption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('poll_option_index', ['poll' => $pollOption->getPoll()->getId()]);
        }
        
        if($pollOption->getPoll()->getUser() == $this->getUser()){
            return $this->render('poll_option/edit.html.twig', [
                'poll' => $pollOption->getPoll(),
                'poll_option' => $pollOption,
                'form' => $form->createView(),
            ]);
        }else{
            return $this->redirectToRoute('poll_index');
        }
    }

    /**
     * @Route("/delete/{id}", name="poll_option_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PollOption $pollOption): Response
    {
        $poll = $pollOption->getPoll();
        if ($this->isCsrfTokenValid('delete'.$pollOption->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($pollOption);
            $entityManager->flush();
        }

        return $this->redirectToRoute('poll_option_index', [
            'poll' => $poll->getId()
        ]);
    }
}
