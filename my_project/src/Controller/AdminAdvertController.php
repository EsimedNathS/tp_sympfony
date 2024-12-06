<?php

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\Advert;
use App\Form\AdminUserType;
use App\Form\AdvertType;
use App\Repository\AdvertRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/admin/advert')]
final class AdminAdvertController extends AbstractController
{
    #[Route(name: 'app_advert_index', methods: ['GET'])]
    public function index(Request $request, AdvertRepository $advertrepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $limit = 2;

        $query = $advertrepository->createQueryBuilder('advert')
            ->orderBy('advert.title', 'ASC')
            ->getQuery();

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $previous = $offset - $limit;
        $next = min(count($paginator), $offset + $limit);

        return $this->render('advert/index.html.twig', [
            'adverts' => $paginator,
            'previous' => $previous >= 0 ? $previous : null,
            'next' => $next < count($paginator) ? $next : null,
        ]);
    }

    #[Route('/new', name: 'app_advert_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $advert = new Advert();
        $advert->setState('draft');
        $form = $this->createForm(AdvertType::class, $advert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($advert);
            $entityManager->flush();

            return $this->redirectToRoute('app_advert_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('advert/new.html.twig', [
            'advert' => $advert,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_advert_show', methods: ['GET'])]
    public function show(Advert $advert): Response
    {
        return $this->render('advert/show.html.twig', [
            'advert' => $advert,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_advert_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Advert $advert, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdvertType::class, $advert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_advert_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('advert/edit.html.twig', [
            'advert' => $advert,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_advert_delete', methods: ['POST'])]
    public function delete(Request $request, Advert $advert, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$advert->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($advert);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_advert_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/publish', name: 'app_advert_publish', methods: ['POST'])]
    public function publish(
        Advert $advert,
        #[Target('advert.state_machine')]
        WorkflowInterface $advertWorkflow,
        EntityManagerInterface $entityManager
    ): Response {
        if ($advertWorkflow->can($advert, 'publish')) {
            $advertWorkflow->apply($advert, 'publish');
            $advert->setPublishedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'L\'annonce a été publiée.');
        } else {
            $this->addFlash('error', 'Impossible de publier cette annonce.');
        }

        return $this->redirectToRoute('app_advert_index');
    }

    #[Route('/{id}/reject', name: 'app_advert_reject', methods: ['POST'])]
    public function reject(
        Advert $advert,
        #[Target('advert.state_machine')]
        WorkflowInterface $advertWorkflow,
        EntityManagerInterface $entityManager
    ): Response {
        if ($advertWorkflow->can($advert, 'reject_from_draft') || $advertWorkflow->can($advert, 'reject_from_published')) {
            $transition = $advert->getState() === 'draft' ? 'reject_from_draft' : 'reject_from_published';
            $advertWorkflow->apply($advert, $transition);
            $entityManager->flush();
            $this->addFlash('success', 'L\'annonce a été rejetée.');
        } else {
            $this->addFlash('error', 'Impossible de rejeter cette annonce.');
        }

        return $this->redirectToRoute('app_advert_index');
    }
}
