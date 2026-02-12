<?php

namespace App\Service;

use App\Entity\Commande;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoicePdfGenerator
{
    public function __construct(private Environment $twig)
    {
    }

    public function generate(Commande $commande): string
    {
        $user = $commande->getClient();

        $html = $this->twig->render('pdf/facture.html.twig', [
            'commande' => $commande,
            'user' => $user,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
