<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @Route("/api", name="api_")
 */
class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function index(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent());

        if (!isset($decoded->login, $decoded->password, $decoded->email, $decoded->firstname, $decoded->lastname)) {
            return new JsonResponse(['error' => 'Les informations requises sont manquantes.'], Response::HTTP_BAD_REQUEST);
        }

        $login = $decoded->login;
        $existingUser = $em->getRepository(User::class)->findOneBy(['login' => $login]);

        if ($existingUser) {
            return new JsonResponse(['error' => 'Ce login est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        $email = $decoded->email;
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        $plaintextPassword = $decoded->password;
        $firstname = $decoded->firstname;
        $lastname = $decoded->lastname;

        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setLogin($login);
        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Registered Successfully']);
    }
}
