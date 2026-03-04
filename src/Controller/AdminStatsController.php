<?php

namespace App\Controller;

use App\Repository\CultureRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/stats')]
class AdminStatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats_index', methods: ['GET'])]
    public function index(
        CultureRepository $cultureRepository,
        RessourceRepository $ressourceRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // 1. Répartition des Cultures (Doughnut Chart)
        $cultureData = $cultureRepository->countCulturesByType();
        $cultureLabels = array_column($cultureData, 'type');
        $cultureCounts = array_column($cultureData, 'count');

        $doughnutChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $doughnutChart->setData([
            'labels' => $cultureLabels,
            'datasets' => [
                [
                    'backgroundColor' => ['#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#1abc9c'],
                    'data' => $cultureCounts,
                ],
            ],
        ]);
        $doughnutChart->setOptions([
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Répartition des Cultures sur la Plateforme',
                    'font' => ['size' => 16, 'weight' => 'bold']
                ],
                'legend' => ['position' => 'bottom']
            ],
            'maintainAspectRatio' => false,
        ]);

        // 2. Statistiques des Stocks Globaux (Horizontal Bar Chart)
        $stockData = $ressourceRepository->sumStocksByName();
        $stockLabels = array_column($stockData, 'name');
        $stockTotals = array_column($stockData, 'total');

        $barChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $barChart->setData([
            'labels' => $stockLabels,
            'datasets' => [
                [
                    'label' => 'Total Stock Restant',
                    'backgroundColor' => 'rgba(26, 67, 49, 0.8)',
                    'borderColor' => '#1A4331',
                    'borderWidth' => 1,
                    'data' => $stockTotals,
                ],
            ],
        ]);
        $barChart->setOptions([
            'indexAxis' => 'y', // Histogramme horizontal
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Statistiques des Stocks Globaux',
                    'font' => ['size' => 16, 'weight' => 'bold']
                ],
                'legend' => ['display' => false]
            ],
            'scales' => [
                'x' => ['beginAtZero' => true]
            ],
            'maintainAspectRatio' => false,
        ]);

        return $this->render('back/admin/stats.html.twig', [
            'doughnutChart' => $doughnutChart,
            'barChart' => $barChart,
        ]);
    }
}
