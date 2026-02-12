<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommandeExcelExporter
{
    /**
     * Export des commandes (achats du client) en Excel.
     *
     * @param Commande[] $commandes
     */
    public function exportMesCommandes(array $commandes): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mes commandes');

        $headers = ['#', 'Date', 'Premier produit', 'Nb articles', 'Total (TND)', 'Statut', 'Adresse livraison'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $this->styleHeader($sheet, 1, count($headers));

        $row = 2;
        foreach ($commandes as $c) {
            $firstItem = $c->getItems()->first();
            $sheet->setCellValue('A' . $row, $c->getId());
            $sheet->setCellValue('B' . $row, $c->getCreatedAt()->format('d/m/Y H:i'));
            $sheet->setCellValue('C' . $row, $firstItem ? $firstItem->getProduit()->getNom() : '—');
            $sheet->setCellValue('D' . $row, $c->getItems()->count());
            $sheet->setCellValue('E' . $row, $c->getMontantTotal());
            $sheet->setCellValue('F' . $row, $c->getStatut());
            $sheet->setCellValue('G' . $row, $c->getAdresseLivraison());
            $row++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->createResponse($spreadsheet, 'mes-commandes');
    }

    /**
     * Export des commandes (ventes du vendeur) en Excel.
     *
     * @param Commande[] $commandes
     */
    public function exportMesVentes(array $commandes): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Commandes sur mes produits');

        $headers = ['#', 'Date', 'Client', 'Email', 'Premier produit', 'Nb articles', 'Total (TND)', 'Statut'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $this->styleHeader($sheet, 1, count($headers));

        $row = 2;
        foreach ($commandes as $c) {
            $client = $c->getClient();
            $firstItem = $c->getItems()->first();
            $clientName = $client ? trim(($client->getFirstName() ?? '') . ' ' . ($client->getLastName() ?? '')) : '—';
            if ($clientName === '') {
                $clientName = $client ? ($client->getEmail() ?? $client->getUserIdentifier()) : '—';
            }
            $sheet->setCellValue('A' . $row, $c->getId());
            $sheet->setCellValue('B' . $row, $c->getCreatedAt()->format('d/m/Y H:i'));
            $sheet->setCellValue('C' . $row, $clientName);
            $sheet->setCellValue('D' . $row, $client ? ($client->getEmail() ?? $client->getUserIdentifier()) : '—');
            $sheet->setCellValue('E' . $row, $firstItem ? $firstItem->getProduit()->getNom() : '—');
            $sheet->setCellValue('F' . $row, $c->getItems()->count());
            $sheet->setCellValue('G' . $row, $c->getMontantTotal());
            $sheet->setCellValue('H' . $row, $c->getStatut());
            $row++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->createResponse($spreadsheet, 'mes-ventes');
    }

    private function styleHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $numCols): void
    {
        $range = 'A' . $row . ':' . chr(64 + $numCols) . $row;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1A4331');
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function autoSizeColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $numCols): void
    {
        for ($i = 1; $i <= $numCols; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    private function createResponse(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(static function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xlsx"');
        return $response;
    }
}
