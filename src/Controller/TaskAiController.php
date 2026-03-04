<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/tasks/ai')]
class TaskAiController extends AbstractController
{
    #[Route('/summarize', name: 'task_ai_summarize', methods: ['POST'])]
    public function summarize(Request $request, \App\Service\HuggingFaceService $hfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (strlen($text) < 10) {
            return new JsonResponse(['error' => 'Le texte est trop court pour être résumé.'], 400);
        }

        $summary = $hfService->summarizeText($text);

        return new JsonResponse(['summary' => $summary]);
    }

    #[Route('/translate', name: 'task_ai_translate', methods: ['POST'])]
    public function translate(Request $request, \App\Service\HuggingFaceService $hfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $targetLanguage = $data['target'] ?? 'en';

        if (strlen($text) < 2) {
            return new JsonResponse(['error' => 'Le texte est trop court pour être traduit.'], 400);
        }

        $translation = $hfService->translateText($text, $targetLanguage);

        return new JsonResponse(['translation' => $translation]);
    }

    #[Route('/recommend', name: 'task_ai_recommend', methods: ['POST'])]
    public function recommend(Request $request, \App\Service\HuggingFaceService $hfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $type = $data['type'] ?? '';

        if (empty($title)) {
            return new JsonResponse(['error' => 'Le titre est requis pour générer une recommandation.'], 400);
        }

        $recommendation = $hfService->recommendDescription($title, $type);
        return new JsonResponse(['recommendation' => $recommendation]);
    }
    #[Route('/analyze-priority', name: 'task_ai_analyze_priority', methods: ['POST'])]
    public function analyzePriority(Request $request, \App\Service\LocalAiAnalyzer $analyzer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';

        $priority = $analyzer->analyzePriority($title, $description);

        return new JsonResponse(['priority' => $priority]);
    }
}
