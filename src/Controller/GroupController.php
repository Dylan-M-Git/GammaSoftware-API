<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\Group;

class GroupController extends AbstractController
{
    private $encoders;
    private $normalizers;
    private $serializer;

    public function __construct()
    {
        $this->encoders = [new JsonEncoder()];
        $this->normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($this->normalizers, $this->encoders);
    }

    /**
     * @Route("/groups", name="group_get_all", methods={"GET"})
     */
    public function getAll(): Response
    {
        $groups = $this->getDoctrine()->getRepository(Group::class)->findAll();

        return new Response($this->serializer->serialize($groups, 'json'), Response::HTTP_OK, ['Content-type' => 'application/json']);
    }

    /**
     * @Route("/group/{id}", name="group_get_by_id", methods={"GET"})
     */
    public function getById(int $id): Response
    {
        $group = $this->getDoctrine()->getRepository(Group::class)->find($id);

        if (!$group) {
            return $this->json(['message' => "No group for id: $id"], 404);
        } else {
            return new Response($this->serializer->serialize($group, 'json'), Response::HTTP_OK, ['Content-type' => 'application/json']);
        }
    }

    /**
     * @Route("/group/{id}", name="group_edit", methods={"PUT"})
     */
    public function edit(int $id, Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $group = $this->getDoctrine()->getRepository(Group::class)->find($id);
        if (!$group) {
            return $this->json(['message' => "No group for id: $id"], 404);
        }

        try {
            $group = $this->serializer->deserialize($request->getContent(), Group::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $group]);
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'message' => 'Wrong data!'
            ], 400);
        }

        $this->getDoctrine()->getRepository(Group::class)->save($group);

        return $this->json([
            'message' => 'Group updated with success!'
        ]);
    }

    /**
     * @Route("/group/{id}", name="group_delete", methods={"DELETE"})
     */
    public function delete(int $id): JsonResponse
    {
        $group = $this->getDoctrine()->getRepository(Group::class)->find($id);
        if (!$group) {
            return $this->json(['message' => "No group for id: $id"], 404);
        }

        $this->getDoctrine()->getRepository(Group::class)->remove($group, true);

        return $this->json([
            'message' => 'Group removed with success!'
        ]);
    }

    /**
     * @Route("/groups", name="group_bulk_create", methods={"POST"})
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $this->getDoctrine()->getRepository(Group::class)->removeAll(); // Suppression de tous les groupes

        $groups = json_decode($request->getContent());    // Passage en tableau pour le parcourir

        foreach ($groups as $group) {
            try {
                $g = $this->serializer->deserialize(json_encode($group), Group::class, 'json'); // Pour transformer en objet Group
            } catch (NotEncodableValueException $e) {
                return $this->json([
                    'message' => 'Wrong data!'
                ], 400);
            }

            $this->getDoctrine()->getRepository(Group::class)->add($g, true);
        }

        return $this->json([
            'message' => 'Groups created with success!'
        ]);
    }
}
