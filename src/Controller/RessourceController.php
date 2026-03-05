<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\HeaderUtils;
#[Route('/ressource')]
#[IsGranted('ROLE_AGRICULTEUR')]
class RessourceController extends AbstractController
{
    #[Route('/', name: 'app_ressource_index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $repo): Response
    {
        $user = $this->getUser();

        // --- FILTRAGE ET PAGINATION ---
        $typeSearch = trim((string) $request->query->get('type', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 8;

        $qb = $repo->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        if ($typeSearch !== '') {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $typeSearch);
        }

        // Compte total pour la pagination
        $qbCount = clone $qb;
        $totalRessources = (int) $qbCount->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($totalRessources / $limit));
        
        if ($page > $totalPages) $page = $totalPages;

        $ressources = $qb->orderBy('r.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Liste des types pour le filtre (dynamique selon ce que l'user possède)
        $typesDisponibles = array_column(
            $repo->createQueryBuilder('rt')
                ->select('DISTINCT rt.type')
                ->where('rt.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getScalarResult(), 
            'type'
        );

        return $this->render('front/semi-public/ressource/ressource.html.twig', [
            'ressources'       => $ressources,
            'typeSearch'       => $typeSearch,
            'currentPage'      => $page,
            'totalPages'       => $totalPages,
            'totalResults'     => $totalRessources,
            'typesDisponibles' => $typesDisponibles,
        ]);
    }

    public function exportExcel(RessourceRepository $repo): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les ressources de l'utilisateur
        $ressources = $repo->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.nom', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Créer un nouveau document Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Définir le titre du document
        $sheet->setTitle('Stock Ressources');
        
        // --- EN-TÊTES ---
        $headers = [
            'A1' => 'ID',
            'B1' => 'NOM DE LA RESSOURCE',
            'C1' => 'CATÉGORIE',
            'D1' => 'UNITÉ',
            'E1' => 'QUANTITÉ EN STOCK',
            'F1' => 'STATUT STOCK'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // --- DONNÉES ---
        $row = 2;
        foreach ($ressources as $ressource) {
            $stock = $ressource->getStockRestant();
            $statut = $stock < 10 ? 'Stock faible' : 'Stock suffisant';
            $couleurStatut = $stock < 10 ? 'FF0000' : '10B981';
            
            $sheet->setCellValue('A' . $row, $ressource->getId());
            $sheet->setCellValue('B' . $row, $ressource->getNom());
            $sheet->setCellValue('C' . $row, $ressource->getType());
            $sheet->setCellValue('D' . $row, $ressource->getUnite());
            $sheet->setCellValue('E' . $row, $stock);
            $sheet->setCellValue('F' . $row, $statut);
            
            // Style pour le statut
            $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB($couleurStatut);
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            
            // Alterner les couleurs de lignes
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            }
            
            $row++;
        }
        
        // Ajuster automatiquement la largeur des colonnes
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Ajouter un résumé en bas
        $lastRow = $row + 1;
        $sheet->setCellValue('A' . $lastRow, 'RÉSUMÉ DU STOCK:');
        $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow)->getFont()->setSize(14);
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, 'Total ressources:');
        $sheet->setCellValue('B' . $lastRow, count($ressources));
        $sheet->getStyle('A' . $lastRow . ':B' . $lastRow)->getFont()->setBold(true);
        
        $lastRow++;
        $stockFaible = 0;
        foreach ($ressources as $r) {
            if ($r->getStockRestant() < 10) $stockFaible++;
        }
        $sheet->setCellValue('A' . $lastRow, 'Stock faible (<10):');
        $sheet->setCellValue('B' . $lastRow, $stockFaible);
        if ($stockFaible > 0) {
            $sheet->getStyle('B' . $lastRow)->getFont()->getColor()->setRGB('FF0000');
        }
        
        // Ajouter la date d'export
        $lastRow = $lastRow + 2;
        $sheet->setCellValue('A' . $lastRow, 'Export généré le:');
        $sheet->setCellValue('B' . $lastRow, date('d/m/Y H:i:s'));
        $sheet->getStyle('A' . $lastRow . ':B' . $lastRow)->getFont()->setItalic(true);
        
        // Créer le fichier Excel
        $writer = new Xlsx($spreadsheet);
        
        // Nom du fichier avec date
        $fileName = 'stock_ressources_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Créer un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        
        // Retourner la réponse avec le fichier
       // ✅ METTRE SEULEMENT ÇA
return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    public function exportCsv(RessourceRepository $repo): Response
    {
        $user = $this->getUser();
        
        $ressources = $repo->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.nom', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Créer le contenu CSV
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Cannot create temporary file for CSV export');
        }
        
        // Ajouter le BOM pour UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes
        fputcsv($handle, ['ID', 'Nom', 'Catégorie', 'Unité', 'Stock', 'Statut'], ';');
        
        // Données
        foreach ($ressources as $r) {
            $stock = $r->getStockRestant();
            $statut = $stock < 10 ? 'Stock faible' : 'Stock suffisant';
            
            fputcsv($handle, [
                $r->getId(),
                $r->getNom(),
                $r->getType(),
                $r->getUnite(),
                $stock,
                $statut
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="stock_ressources_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ressource = new Ressource();
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }
        $ressource->setUser($user);
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // --- CONTRÔLE DE SAISIE BACKEND ---
            if ($form->isValid()) {
                $em->persist($ressource);
                $em->flush();

                $this->addFlash('success', 'Ressource ajoutée avec succès !');
                return $this->redirectToRoute('app_ressource_index');
            } else {
                // Gestion des erreurs backend
                $this->addFlash('danger', 'Le formulaire contient des erreurs. Veuillez vérifier vos données.');
            }
        }

        return $this->render('front/semi-public/ressource/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        // Sécurité : vérifier que la ressource appartient bien à l'user connecté
        if ($ressource->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('info', 'Ressource mise à jour avec succès.');
                return $this->redirectToRoute('app_ressource_index');
            } else {
                $this->addFlash('danger', 'Échec de la mise à jour : données invalides.');
            }
        }

        return $this->render('front/semi-public/ressource/edit.html.twig', [
            'form' => $form->createView(),
            'ressource' => $ressource
        ]);
    }

    #[Route('/{id}/delete', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($ressource->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), (string) $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('warning', 'Ressource supprimée de votre stock.');
        }

        return $this->redirectToRoute('app_ressource_index');
    }
}