<?php

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users", methods={"GET"})
     */
    public function getCurrentUser(TokenStorageInterface $tokenStorage, SerializerInterface $serializer): Response
    {
        $user = $tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $serializer->serialize($user, 'json', [
            'groups' => 'user'
        ]);
        return new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }


    /**
     * @Route("/api/users", methods={"PUT"})
     */
    public function updateCurrentUser(
        Request $request,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        SerializerInterface $serializer
    ): Response {
        $user = $tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Check if JSON is empty
        if (empty($data)) {
            return $this->json(['error' => 'Empty JSON provided'], Response::HTTP_BAD_REQUEST);
        }

        // Define allowed keys
        $allowedKeys = ['login', 'email', 'firstname', 'lastname'];

        // Check for invalid keys
        $invalidKeys = array_diff(array_keys($data), $allowedKeys);
        if (!empty($invalidKeys)) {
            return $this->json(['error' => 'Invalid keys provided: ' . implode(', ', $invalidKeys)], Response::HTTP_BAD_REQUEST);
        }

        // Update user properties
        if (isset($data['login'])) {
            $user->setLogin($data['login']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }

        $userRepository->save($user);

        $responseData = $serializer->serialize($user, 'json', ['groups' => 'user']);
        return new Response($responseData, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
