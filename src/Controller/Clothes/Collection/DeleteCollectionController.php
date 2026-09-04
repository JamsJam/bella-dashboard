<?php

namespace App\Controller\Clothes\Collection;

use App\Entity\Collections\Collections;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCollectionController extends AbstractController
{
    #[Route('/collections/{id}/delete', name: 'app_clothe_collection_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Collections $collection,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerService $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('collection_delete_' . $collection->getId(), (string) $request->request->get('_csrf_token', ''))) {
            $logger->warning('Invalid CSRF token for collection deletion.', ['collection_id' => $collection->getId()]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $id = (int) $collection->getId();
        $rowId = 'collection-row-' . $id;
        $entityManager->remove($collection);
        $entityManager->flush();
        $logger->info('Collection deleted.', ['collection_id' => $id, 'row_id' => $rowId]);

        return new Response(
            sprintf('<turbo-stream action="remove" target="%s"></turbo-stream>', $rowId),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
