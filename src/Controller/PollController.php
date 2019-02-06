<?php

namespace App\Controller;

use App\Entity\Poll;
use App\Form\PollType;
use App\Repository\PollRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\PollOption;
use App\Form\PollOptionType;
use App\Entity\PollVote;
use App\Repository\PollOptionRepository;
use App\Repository\PollVoteRepository;

/**
 * @Route("/poll")
 */
class PollController extends AbstractController
{
    /**
     * @Route("/", name="poll_index", methods={"GET"})
     */
    public function index(PollRepository $pollRepository): Response
    {
        return $this->render('poll/index.html.twig', [
            'polls' => $pollRepository->findBy(
                ['user' => $this->getUser()]
            )
        ]);
    }

    /**
     * @Route("/new", name="poll_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $poll = new Poll();
        $form = $this->createForm(PollType::class, $poll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $poll->setUser($this->getUser());
            $entityManager->persist($poll);
            $entityManager->flush();

            return $this->redirectToRoute('poll_index');
        }

        return $this->render('poll/new.html.twig', [
            'poll' => $poll,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="poll_show", methods={"GET", "POST"})
     */
    public function show(Poll $poll, Request $request, PollOptionRepository $pollOptionRepository, PollVoteRepository $pollVoteRepository): Response
    {
        $builder = $this->createFormBuilder();
        $choices = [];
        foreach ($poll->getPollOptions() as $option) {
            $choices[$option->getName()] = $option->getId();
        }
        $builder->add('choice', ChoiceType::class, [
            'label' => 'Votre réponse:',
            'choices' => $choices,
            'expanded' => true
        ]);
        $builder->add('ok', SubmitType::class, [
            'label' => 'Voter'
        ]);
        $form = $builder->getForm();
        
        //Gestion du vote
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { //Si le formulaire est valide
            
            $pollVote = new PollVote();
            $entityManager = $this->getDoctrine()->getManager();
            $pollVote->setUser($this->getUser());
            $pollOption = $pollOptionRepository->find($form->getData()["choice"]);//récupération du choix de l'utilisateur
            $pollVote->setPollOption($pollOption);
            $pollVote->setPoll($poll);
            $nbVote = count($pollVoteRepository->findBy([//Si c'est la première fois que l'utilisateur vote
                'user' => $this->getUser(),
                'poll' => $poll->getId() 
            ]));
            if(!$nbVote>=1){
                $entityManager->persist($pollVote);
                $entityManager->flush();
                return $this->redirectToRoute('poll_show',[
                    'id' => $poll->getId()
                ]);
            }
        }
        $nbVote = count($pollVoteRepository->findBy([//Si c'est la première fois que l'utilisateur vote
            'user' => $this->getUser(),
            'poll' => $poll->getId() 
        ]));
        return $this->render('poll/show.html.twig', [
            'poll' => $poll,
            'nbVote' => $nbVote,
            'form' => $form->createView(),
            //Vote de l'utilisateur
            'pollVote' => $pollVoteRepository->findBy(
                ['user' => $this->getUser(),
                'poll' => $poll,
                ]
            )
        ]);
    }

    /**
     * @Route("/{id}/edit", name="poll_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Poll $poll, PollRepository $pollRepository): Response
    {
        $form = $this->createForm(PollType::class, $poll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('poll_index', ['id' => $poll->getId()]);
        }

        if($poll->getUser() == $this->getUser()){
            return $this->render('poll/edit.html.twig', [
                'poll' => $poll,
                'form' => $form->createView(),
            ]);
        }else{
            return $this->redirectToRoute('poll_index');
        }
    }

    /**
     * @Route("/{id}", name="poll_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Poll $poll): Response
    {
        if ($this->isCsrfTokenValid('delete'.$poll->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($poll);
            $entityManager->flush();
        }

        return $this->redirectToRoute('poll_index');
    }
}
