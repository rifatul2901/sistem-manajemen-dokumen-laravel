<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentService
{
    public function detectPlaceholderCounts($templateSource, array $validColumns): array
    {
        $content = $this->getDocxContent($templateSource);
        $columnLookup = array_change_key_case(array_flip($validColumns), CASE_LOWER);
        $counts = [];

        preg_match_all('/\{\{\s*(\w+(?:\s+\w+)*)\s*\}\}/', $content, $matches);
        foreach ($matches[1] as $tag) {
            $tagLower = strtolower(trim(preg_replace('/\s+/', '', $tag)));
            if (isset($columnLookup[$tagLower])) {
                $originalCol = $validColumns[$columnLookup[$tagLower]];
                $counts[$originalCol] = ($counts[$originalCol] ?? 0) + 1;
            }
        }

        return $counts;
    }

    public function generateMultiKalimat($templateSource, array $selectedRecords, array $extraContext, string $outputPath): void
    {
        $tempDir = dirname($outputPath) . '/_temp_' . uniqid();
        $this->extractDocx($templateSource, $tempDir);

        $docXmlPath = $tempDir . '/word/document.xml';
        if (!file_exists($docXmlPath)) {
            throw new \RuntimeException('Format template DOCX tidak valid.');
        }

        $dom = new \DOMDocument();
        $dom->load($docXmlPath);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $columnLookup = [];
        if (!empty($selectedRecords)) {
            foreach ($selectedRecords[0] as $col => $val) {
                $columnLookup[strtolower($col)] = $col;
            }
        }

        $counters = [];

        // Process each paragraph
        $paragraphs = $xpath->query('//w:p');
        foreach ($paragraphs as $para) {
            $textNodes = $xpath->query('.//w:t', $para);
            if ($textNodes->length === 0) continue;

            // Collect all text content
            $fullText = '';
            foreach ($textNodes as $tn) {
                $fullText .= $tn->textContent;
            }

            // Check if this paragraph has placeholders
            if (!preg_match('/\{\{/', $fullText)) continue;

            // Replace placeholders
            $fullText = preg_replace_callback('/\{\{\s*(\w+(?:\s+\w+)*)\s*\}\}/', function ($match) use ($columnLookup, $selectedRecords, $extraContext, &$counters) {
                $tag = trim(preg_replace('/\s+/', '', $match[1]));
                $tagLower = strtolower($tag);

                if (isset($extraContext[$tag])) {
                    return $extraContext[$tag];
                }

                $originalCol = $columnLookup[$tagLower] ?? null;
                if (!$originalCol) {
                    return $match[0];
                }
                $idx = $counters[$originalCol] ?? 0;
                $counters[$originalCol] = $idx + 1;
                if ($idx < count($selectedRecords)) {
                    $val = $selectedRecords[$idx][$originalCol] ?? '';
                    return htmlspecialchars($val, ENT_XML1, 'UTF-8');
                }
                return '';
            }, $fullText);

            // Put result back into first text node, remove others
            $textNodes->item(0)->nodeValue = $fullText;
            for ($i = 1; $i < $textNodes->length; $i++) {
                $tn = $textNodes->item($i);
                $tn->parentNode->removeChild($tn);
            }
        }

        $dom->save($docXmlPath);

        $this->zipDocx($tempDir, $outputPath);

        $this->rrmdir($tempDir);

        $templateProcessor = new TemplateProcessor($outputPath);
        foreach ($extraContext as $key => $val) {
            $templateProcessor->setValue($key, $val);
        }
        $templateProcessor->saveAs($outputPath);
    }

    public function convertDocxToPdf(string $docxPath, string $pdfPath): void
    {
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);

        $phpWord = IOFactory::load($docxPath);
        $xmlWriter = IOFactory::createWriter($phpWord, 'PDF');
        $xmlWriter->save($pdfPath);
    }

    public function createRevisionPdf(string $filename, array $catatan, string $outputPath): string
    {
        $tanggal = now()->format('d/m/Y H:i');
        $namaBerkas = $filename;
        $catatanList = $catatan;
        $pdf = app('dompdf.wrapper');
        $html = view('admin.revision-notification', compact('namaBerkas', 'tanggal', 'catatanList'))->render();
        $pdf->loadHTML($html);
        $pdf->save($outputPath);
        return $outputPath;
    }

    protected function getDocxContent($templateSource): string
    {
        $path = is_string($templateSource) && file_exists($templateSource)
            ? $templateSource
            : storage_path('app/templates/template_multinama.docx');

        $tempDir = sys_get_temp_dir() . '/_docx_' . uniqid();
        $this->extractDocx($path, $tempDir);

        $docXmlPath = $tempDir . '/word/document.xml';
        $content = '';
        if (file_exists($docXmlPath)) {
            $xml = simplexml_load_file($docXmlPath);
            $namespaces = $xml->getNamespaces(true);
            if (isset($namespaces['w'])) {
                $xml->registerXPathNamespace('w', $namespaces['w']);
                $texts = $xml->xpath('//w:t');
                if ($texts) {
                    foreach ($texts as $t) {
                        $content .= (string)$t . ' ';
                    }
                }
            }
        }

        $this->rrmdir($tempDir);
        return $content;
    }

    protected function extractDocx(string $docxPath, string $destDir): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Gagal membuka template DOCX.');
        }
        $zip->extractTo($destDir);
        $zip->close();
    }

    protected function zipDocx(string $sourceDir, string $outputPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file DOCX output.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1));
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();
    }

    protected function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    protected function resolveTemplatePath($templateSource): string
    {
        if (is_string($templateSource) && file_exists($templateSource)) {
            return $templateSource;
        }
        return storage_path('app/templates/template_multinama.docx');
    }

    public static function bersihkanNamaFile(string $teks): string
    {
        $teks = trim(str_replace(' ', '_', $teks));
        return preg_replace('/[\\\\\/:*?"<>|]/', '', $teks);
    }
}
