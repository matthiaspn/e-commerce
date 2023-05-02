<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Repository\CatalogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CatalogController extends AbstractController
{
    /**
     * @Route("/api/products", methods={"GET"})
     */
    public function getAllProducts(CatalogRepository $catalogRepository): JsonResponse
    {
        $catalogs = $catalogRepository->findAll();

        if (empty($catalogs)) {
            return $this->json(['error' => 'No product in the catalog.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($catalogs);
    }

    /**
     * @Route("/api/products/{id}", methods={"GET"})
     */
    public function getProductById(string $id, CatalogRepository $catalogRepository): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'Invalid parameter. ID must be a number.'], Response::HTTP_BAD_REQUEST);
        }

        $catalog = $catalogRepository->find($id);

        if (!$catalog) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($catalog);
    }

    /**
     * @Route("/api/products", methods={"POST"})
     */
    public function createProduct(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['description']) || empty($data['photo']) || empty($data['price'])) {
            return $this->json(['error' => 'Missing parameters. All fields are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($data['price'])) {
            return $this->json(['error' => 'Invalid parameter. Price must be a number.'], Response::HTTP_BAD_REQUEST);
        }

        $catalog = new Catalog();
        $catalog->setName($data['name']);
        $catalog->setDescription($data['description']);
        $catalog->setPhoto($data['photo']);
        $catalog->setPrice($data['price']);

        $entityManager->persist($catalog);
        $entityManager->flush();

        return $this->json($catalog);
    }

    /**
     * @Route("/api/products/{id}", methods={"PUT"})
     */
    public function updateProduct(string $id, Request $request, CatalogRepository $catalogRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'Invalid parameter. ID must be a number.'], Response::HTTP_BAD_REQUEST);
        }

        $catalog = $catalogRepository->find($id);

        if (!$catalog) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Update fields if provided in request
        if (!empty($data['name'])) {
            $catalog->setName($data['name']);
        }

        if (!empty($data['description'])) {
            $catalog->setDescription($data['description']);
        }

        if (!empty($data['photo'])) {
            $catalog->setPhoto($data['photo']);
        }

        if (!empty($data['price'])) {
            if (!is_numeric($data['price'])) {
                return $this->json(['error' => 'Invalid parameter. Price must be a number.'], Response::HTTP_BAD_REQUEST);
            }

            $catalog->setPrice($data['price']);
        }

        $entityManager->flush();

        return $this->json($catalog);
    }

    /**
     * @Route("/api/products/{id}", methods={"DELETE"})
     */
    public function deleteProduct(string $id, CatalogRepository $catalogRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'Invalid parameter. ID must be a number.'], Response::HTTP_BAD_REQUEST);
        }

        $catalog = $catalogRepository->find($id);

        if (!$catalog) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($catalog);
        $entityManager->flush();

        return $this->json(['message' => 'Product deleted.']);
    }
}
