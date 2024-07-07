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

class ProductController extends AbstractController
{
    public $dateNow;

    public function __construct() {
        $this->dateNow = date("Y-m-d H:i:s");
    }

    #[Route('/products', name: 'product_list')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sort = $request->query->get('sort', 'name');
        $direction = $request->query->get('direction', 'asc');

        $allowedColumns = ['name', 'description', 'price', 'stockQuantity', 'createdDatetime'];
        if (!in_array($sort, $allowedColumns)) {
            throw $this->createNotFoundException('Invalid sort column.');
        }

        $priceMin = $request->request->get('price_min');
        $priceMax = $request->request->get('price_max');
        $stockMin = $request->request->get('stock_min');
        $stockMax = $request->request->get('stock_max');
        $dateFrom = $request->request->get('date_from');
        $dateTo = $request->request->get('date_to');

        $currentPage = $request->query->getInt('page', 1);

        $queryBuilder = $entityManager->getRepository(Product::class)->createQueryBuilder('p');

        if ($priceMin != "") {
            $queryBuilder->andWhere('p.price >= :priceMin')->setParameter('priceMin', $priceMin);
        }

        if ($priceMax != "") {
            $queryBuilder->andWhere('p.price <= :priceMax')->setParameter('priceMax', $priceMax);
        }

        if ($stockMin != "") {
            $queryBuilder->andWhere('p.stockQuantity >= :stockMin')->setParameter('stockMin', $stockMin);
        }

        if ($stockMax != "") {
            $queryBuilder->andWhere('p.stockQuantity <= :stockMax')->setParameter('stockMax', $stockMax);
        }

        if ($dateFrom != "") {
            $queryBuilder->andWhere('p.createdDatetime >= :dateFrom')->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo != "") {
            $queryBuilder->andWhere('p.createdDatetime <= :dateTo')->setParameter('dateTo', new \DateTime($dateTo));
        }

        $queryBuilder->orderBy('p.' . $sort, $direction);

        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setCurrentPage($currentPage);
        $pagerfanta->setMaxPerPage(10);

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        return $this->render('product/index.html.twig', [
            'products' => $pagerfanta,
            'sort' => $sort,
            'direction' => $direction,
            'request' => $request,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/products/import', name: 'product_import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        $file = $request->files->get('csv_file');
        if ($file) {
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
        }

        return $this->redirectToRoute('product_list');
    }

    #[Route('/products/export', name: 'product_export')]
    public function export(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

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

    #[Route('/products/{id}/delete', name: 'product_delete', methods: ['GET'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->query->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'There was an error.');
        }
    
        return $this->redirectToRoute('product_list');
    }
}
