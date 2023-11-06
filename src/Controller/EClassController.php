<?php

namespace App\Controller;

use App\Entity\EClass;
use App\Repository\EClassRepository;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EClassController extends AbstractController
{
    private EClassRepository $repository;
    private TranslatableListener $translationListener;
    public function __construct(
        EClassRepository $repository,
        TranslatableListener $translationListener
    ) {
        $this->repository = $repository;
        $this->translationListener = $translationListener;
    }
    /**
     * @Route("/e-class-tree/{code<\d+>?}", name="e-class-tree", methods={"GET"})
     *
     * @param int|null $code
     * @param Request $request
     * @return JsonResponse
     */
    public function getEClassTree(Request $request, int $code = null): JsonResponse {
        $this->denyAccessUnlessGranted('SOME_ROLE');

        $code = $code ?? EClass::ROOT_CODE;

        $currentClass = $this->repository->findOneBy(['code' => $code]);
        if (!$currentClass) {
            throw new NotFoundHttpException("EClass by code: $code is not found");
        }

        $eClassTree = [];
        $currentChildren = $this->repository->findChildrenFrom($code);
        if ($currentChildren) {
            $eClassTree = array_merge($eClassTree, $this->mapEClass($currentChildren));
        }

        while ($parentClass = $this->repository->findOneBy(['code' => $currentClass->getParentFK()])) {
            $children = $this->repository->findChildrenFrom($parentClass->getCode());
            if (!$children) {
                throw new NotFoundHttpException(sprintf(
                    "Can not find children for EClass parent code: s% ", $parentClass->getCode()
                    ));
            }

            $eClassTree = array_merge($eClassTree, $this->mapEClass($children, $currentClass->getCode()));

            $currentClass = $parentClass;
        }

        return new JsonResponse($eClassTree, 200);
    }

    /**
     * @param array $children
     * @param int|null $code
     * @return array|array[]
     */
    private function mapEClass(array $children, int $code = null): array {
        return array_map(function(EClass $child) use ($code) {
            return [
                'name' => $child->getCombinedName(),
                'code' => $child->getCode(),
                'parent' => $child->getParentFK() === EClass::ROOT_CODE ? null : $child->getParentFK(),
                'selected' => $child === $code
            ];
        }, $children);
    }
}
