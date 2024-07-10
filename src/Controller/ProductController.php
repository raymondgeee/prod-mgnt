<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ProductService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductController extends AbstractController
{
    public $dateNow;
    private $productService;

    public function __construct(ProductService $productService) {
        date_default_timezone_set('Asia/Singapore');
        
        $this->dateNow = date("Y-m-d H:i:s");
        $this->productService = $productService;
    }

    #[Route('/', name: 'product_list')]
    public function list(Request $request): Response
    {
        $sort = $request->query->get('sort', 'name');
        $direction = $request->query->get('direction', 'asc');
        $show_entries = $request->query->get('show_entries', 10);

        $allowedColumns = ['name', 'description', 'price', 'stockQuantity', 'createdDatetime'];
        if (!in_array($sort, $allowedColumns)) {
            throw $this->createNotFoundException('Invalid sort column.');
        }

        $filters = [
            'product_name' => $request->query->get('product_name', ''),
            'product_desc' => $request->query->get('product_desc', ''),
            'price_min' => $request->query->get('price_min', ''),
            'price_max' => $request->query->get('price_max', ''),
            'stock_min' => $request->query->get('stock_min', ''),
            'stock_max' => $request->query->get('stock_max', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
        ];

        $currentPage = $request->query->getInt('page', 1);

        $queryBuilder = $this->productService->getFilters($filters);
        $queryBuilder->orderBy('p.' . $sort, $direction);

        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setCurrentPage($currentPage);
        $pagerfanta->setMaxPerPage($show_entries);

        $count = count($pagerfanta);

        return $this->render('product/index.html.twig', [
            'products' => $pagerfanta,
            'sort' => $sort,
            'direction' => $direction,
            'request' => $request,
            'filters' => $filters,
            'count' => $count,
            'show_entries' => $show_entries,
        ]);
    }


    #[Route('/products/import', name: 'product_import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        $file = $request->files->get('csv_file');
        if ($file && $file instanceof UploadedFile) {

            $mimeType = $file->getMimeType();
            if ($mimeType !== 'text/csv' && $mimeType !== 'text/plain') {
                $this->addFlash('error', 'Invalid file type. Please upload a CSV file.');
                return $this->redirectToRoute('product_list');
            }

            try {
                $csv = Reader::createFromPath($file->getPathname(), 'r');
                $csv->setHeaderOffset(0);

                foreach ($csv as $row) {
                    $product = new Product();
                    $product->setName($row['name']);
                    $product->setDescription($row['description']);
                    $product->setPrice($row['price']);
                    $product->setStockQuantity($row['stock_quantity']);
                    $product->setCreatedDatetime(new \DateTime($this->dateNow));

                    $entityManager->persist($product);
                }

                $entityManager->flush();

                $this->addFlash('success', 'CSV imported successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while importing csv file: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'No file uploaded.');
        }

        return $this->redirectToRoute('product_list');
    }

    #[Route('/products/export', name: 'product_export')]
    public function export(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        $count = $this->productService->countProducts();

        if($count > 0) {
            $csv = Writer::createFromString('');
            $csv->insertOne(['id', 'name', 'description', 'price', 'stock_quantity', 'created_datetime']);

            foreach ($products as $product) {
                $csv->insertOne([
                    $product->getId(),
                    $product->getName(),
                    $product->getDescription(),
                    $product->getPrice(),
                    $product->getStockQuantity(),
                    $product->getCreatedDatetime()->format('Y-m-d H:i:s'),
                ]);
            }

            return new Response($csv->getContent(), 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="products.csv"',
            ]);
        } else {
            $this->addFlash('error', 'No data to be exported.');
            return $this->redirectToRoute('product_list');
        }
    }

    #[Route('/products/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('product_new'),
            'method' => 'POST',
        ]);

        $product->setCreatedDatetime(new \DateTime($this->dateNow));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success', 'Product created successfully.');

                return $this->redirectToRoute('product_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while creating the product: ' . $e->getMessage());
            }
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/products/{id}/edit', name: 'product_edit')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('product_edit', ['id' => $product->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Product updated successfully.');

                return $this->redirectToRoute('product_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the product: ' . $e->getMessage());
            }
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/products/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return new JsonResponse([
                'data' => 'error',
                'msg' => 'Product not found.',
            ]);
        }

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            return new JsonResponse([
                'data' => 'success',
                'msg' => 'Product deleted successfully.',
            ]);
        } else {
            return new JsonResponse([
                'data' => 'error',
                'msg' => 'Invalid CSRF token.',
            ]);
        }
    }
}
