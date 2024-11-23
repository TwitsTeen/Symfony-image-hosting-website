<?php

namespace App\Controller;

use App\Entity\UploadedImage;
use App\Form\UploadedImageType;
use App\Repository\UploadedImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/uploaded/image')]
final class UploadedImageController extends AbstractController{
    #[Route(name: 'app_uploaded_image_index', methods: ['GET'])]
    public function index(UploadedImageRepository $uploadedImageRepository): Response
    {
        return $this->render('uploaded_image/index.html.twig', [
            'uploaded_images' => $uploadedImageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_uploaded_image_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    $uploadedImage = new UploadedImage();
    $form = $this->createForm(UploadedImageType::class, $uploadedImage);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Generate a safe filename
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            // Move the file to the uploads directory
            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Handle the exception, e.g., log it or show a user-friendly message
                throw new \Exception('An error occurred while uploading the file.');
            }

            // Set the image path in the entity
            $uploadedImage->setImageUrl($newFilename);
        }

        $entityManager->persist($uploadedImage);
        $entityManager->flush();

        return $this->redirectToRoute('app_uploaded_image_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('uploaded_image/new.html.twig', [
        'uploaded_image' => $uploadedImage,
        'form' => $form->createView(),
    ]);
}
    #[Route('/{id}', name: 'app_uploaded_image_show', methods: ['GET'])]
    public function show(UploadedImage $uploadedImage): Response
    {
        return $this->render('uploaded_image/show.html.twig', [
            'uploaded_image' => $uploadedImage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_uploaded_image_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UploadedImage $uploadedImage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UploadedImageType::class, $uploadedImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_uploaded_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('uploaded_image/edit.html.twig', [
            'uploaded_image' => $uploadedImage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_uploaded_image_delete', methods: ['POST'])]
    public function delete(Request $request, UploadedImage $uploadedImage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$uploadedImage->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($uploadedImage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_uploaded_image_index', [], Response::HTTP_SEE_OTHER);
    }
}
