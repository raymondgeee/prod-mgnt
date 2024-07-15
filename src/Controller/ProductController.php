<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProductController extends AbstractController
{
    public $dateNow;
    private $productService;
    private $logger;

    public function __construct(ProductService $productService, LoggerInterface $logger) {
        date_default_timezone_set('Asia/Singapore');
        
        $this->dateNow = date("Y-m-d H:i:s");
        $this->productService = $productService;
        $this->logger = $logger;
    }

    #[Route('/', name: 'product_list')]
    public function list(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);
        $queryParams = $request->query->all();
       
        $allowedColumns = ['name', 'description', 'price', 'stockQuantity', 'createdDatetime'];

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

        if ($request->isXmlHttpRequest()) {
            $queryBuilder = $this->productService->getFilters($filters);
            
            if (!empty($queryParams['order'])) {
                foreach ($queryParams['order'] as $sortOrder) {
                    $columnIndex = $sortOrder['column'];
                    $sortDirection = $sortOrder['dir'];
                    $sortColumn = $allowedColumns[$columnIndex];

                    $queryBuilder->addOrderBy('p.' . $sortColumn, $sortDirection);
                }
            }

            $totalRecords = count($queryBuilder->getQuery()->getResult());

            $queryBuilder->setFirstResult($start)
                        ->setMaxResults($length);

            $products = $queryBuilder->getQuery()->getArrayResult();

            foreach ($products as &$product) {
                if (isset($product['createdDatetime']) && $product['createdDatetime'] instanceof \DateTime) {
                    $product['createdDatetime'] = $product['createdDatetime']->format('Y-m-d H:i:s');
                    $product['token'] = $csrfTokenManager->getToken('delete' . $product['id'])->getValue();
                }
            }
            
            return $this->json([
                'data' => $products,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ]);
        }
       
        return $this->render('product/index.html.twig');
    }

    #[Route('/products/import', name: 'product_import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        $file = $request->files->get('csv_file');
        if ($file && $file instanceof UploadedFile) {
            $mimeType = $file->getMimeType();
            if ($mimeType !== 'text/csv' && $mimeType !== 'text/plain') {
                $this->logger->error("Invalid file type. Please upload a CSV file.");
                $this->addFlash('error', 'Invalid file type. Please upload a CSV file.');
                return $this->redirectToRoute('product_list');
            }

            try {
                $filePath = $file->getPathname();

                $importedData = $this->productService->importFromCSV($filePath);

                foreach ($importedData as $row) {
                    $product = new Product();
                    $product->setName($row['name']);
                    $product->setDescription($row['description']);
                    $product->setPrice($row['price']);
                    $product->setStockQuantity($row['stock_quantity']);
                    $product->setCreatedDatetime(new \DateTime($this->dateNow));
                    $entityManager->persist($product);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Products imported successfully.');

                return $this->redirectToRoute('product_list');
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload file.');
                $this->logger->error("Failed to upload file.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to import data.');
                $this->logger->error("Failed to import data.");
            }
        }

        return $this->render('product/index.html.twig', [
            'error' => 'Please upload a valid CSV file.',
        ]);
    }

    #[Route('/products/export', name: 'product_export')]
    public function export(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        $count = $this->productService->countProducts();

        if($count > 0) {
            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    "ID" => $product->getId(),
                    "Name" => $product->getName(),
                    "Description" => $product->getDescription(),
                    "Price" => $product->getPrice(),
                    "Stock Quantity" => $product->getStockQuantity(),
                    "Created Datetime" => $product->getCreatedDatetime()->format('Y-m-d H:i:s'),
                ];
            }

            $csvData = $this->productService->arrayToCsv($data);

            $response = new Response($csvData);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');

            return $response;
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
