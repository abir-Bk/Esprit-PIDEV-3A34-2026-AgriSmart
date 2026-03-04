<?php

namespace App\Service;

use Knp\Snappy\GeneratorInterface;
use Twig\Environment;

class PdfService
{
    private Environment $twig;
    private GeneratorInterface $snappy;

    public function __construct(Environment $twig, GeneratorInterface $snappy)
    {
        $this->twig = $twig;
        $this->snappy = $snappy;
    }

    /**
     * Generates a PDF content from a template.
     */
    public function generatePdfResponse(string $template, array $data, string $filename): string
    {
        $html = $this->twig->render($template, $data);

        // KnpSnappy options for professional rendering
        $options = [
            'encoding' => 'utf-8',
            'page-size' => 'A4',
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
        ];

        return $this->snappy->getOutputFromHtml($html, $options);
    }
}
