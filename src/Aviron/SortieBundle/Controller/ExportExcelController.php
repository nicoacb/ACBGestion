<?php

namespace Aviron\SortieBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Aviron\SortieBundle\Statistiques\StatistiquesSorties;

// Include PhpSpreadsheet required namespaces
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportExcelController extends Controller
{
    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function exportKmParcourusParMembreAction(Request $request, $annee, $mois)
    {
        $statistiques = new StatistiquesSorties($this->getDoctrine()->getManager()->getRepository('AvironSortieBundle:Sortie'), $annee, $mois);
        $listeStatistiques = $statistiques->getStatistiquesParMembre()->getStatistiques();        
        usort($listeStatistiques, array($this, "triKmParcourusDesc"));
        
        return $this->DonneClasseurExcel('Membre', $listeStatistiques);
    }

    /**
    * @Security("has_role('ROLE_ADMIN')")
    */
    public function exportKmParcourusParBateauAction(Request $request, $annee, $mois)
    {
        $statistiques = new StatistiquesSorties($this->getDoctrine()->getManager()->getRepository('AvironSortieBundle:Sortie'), $annee, $mois);
        $listeStatistiques = $statistiques->getStatistiquesParBateau()->getStatistiques();        
        usort($listeStatistiques, array($this, "triKmParcourusDesc"));
        
        return $this->DonneClasseurExcel('Bateau', $listeStatistiques);
    }

    private function triKmParcourusDesc($a, $b)
    {
        return $a->getKmParcourus() < $b->getKmParcourus();
    }

    private function DonneClasseurExcel($label, $listeStatistiques)
    {
        $spreadsheet = new Spreadsheet();
        
        /* @var $sheet \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Statistiques");
        $sheet->setCellValue('A1', $label);
        $sheet->setCellValue('B1', 'Km parcourus');
        $sheet->setCellValue('C1', 'Nombre de sorties');

        $ligne = 2;
        foreach ($listeStatistiques as $stat)
        {
            $sheet->setCellValueByColumnAndRow(1, $ligne, $stat->getLabel());
            $sheet->setCellValueByColumnAndRow(2, $ligne, $stat->getKmParcourus());
            $sheet->setCellValueByColumnAndRow(3, $ligne, $stat->getNombreDeSorties());
            $ligne++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Statistiques.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}