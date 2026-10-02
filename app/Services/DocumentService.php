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
        $columnLookup = [];
        foreach ($validColumns as $col) {
            $key = strtolower(preg_replace('/\s+/', '', trim((string) $col)));
            if ($key !== '' && !isset($columnLookup[$key])) {
                $columnLookup[$key] = $col;
            }
        }
        $counts = [];

        preg_match_all('/\{\{\s*([\w\/-]+(?:\s+[\w\/-]+)*)\s*\}\}/', $content, $matches);
        foreach ($matches[1] as $tag) {
            $tagLower = strtolower(trim(preg_replace('/\s+/', '', $tag)));
            if (isset($columnLookup[$tagLower])) {
                $originalCol = $columnLookup[$tagLower];
                $counts[$originalCol] = ($counts[$originalCol] ?? 0) + 1;
            }
        }

        return $counts;
    }

    public function detectTags($templateSource, array $columns): array
    {
        $content = $this->getDocxContent($templateSource);
        $lookup = [];
        foreach ($columns as $col) {
            $key = strtolower(preg_replace('/\s+/', '', trim((string) $col)));
            if ($key !== '' && !isset($lookup[$key])) {
                $lookup[$key] = $col;
            }
        }

        preg_match_all('/\{\{\s*([\w\/-]+(?:\s+[\w\/-]+)*)\s*\}\}/', $content, $matches);
        $seen = [];
        $out = [];
        foreach ($matches[1] as $tag) {
            $tag = trim($tag);
            if (preg_match('/^data\s+\d+$/i', $tag)) {
                continue;
            }
            $key = strtolower(preg_replace('/\s+/', '', $tag));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['tag' => $tag, 'key' => $key, 'matched' => $lookup[$key] ?? null];
        }

        return $out;
    }

    public function generateFromData(string $templatePath, array $selectedData, array $extraContext, string $outputPath): void
    {
        $tempDir = dirname($outputPath) . '/_temp_' . uniqid();
        $this->extractDocx($templatePath, $tempDir);

        $docXmlPath = $tempDir . '/word/document.xml';
        if (!file_exists($docXmlPath)) {
            throw new \RuntimeException('Format template DOCX tidak valid.');
        }

        $dom = new \DOMDocument();
        $dom->load($docXmlPath);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = $xpath->query('//w:p');
        foreach ($paragraphs as $para) {
            $textNodes = $xpath->query('.//w:t', $para);
            if ($textNodes->length === 0) continue;

            $fullText = '';
            foreach ($textNodes as $tn) {
                $fullText .= $tn->textContent;
            }

            if (!preg_match('/\{\{/', $fullText)) continue;

            $fullText = preg_replace_callback('/\{\{\s*data\s+(\d+)\s*\}\}/', function ($match) use ($selectedData) {
                $idx = (int)$match[1] - 1;
                return $selectedData[$idx] ?? $match[0];
            }, $fullText);

            $fullText = preg_replace_callback('/\{\{\s*([\w\/-]+(?:\s+[\w\/-]+)*)\s*\}\}/', function ($match) use ($extraContext) {
                $tag = trim($match[1]);
                return $extraContext[$tag] ?? $match[0];
            }, $fullText);

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

    public function generateFromRow(string $templatePath, array $rowMap, array $extraContext, string $outputPath, array $tagMapping = []): void
    {
        $str = fn($v) => (is_scalar($v) || $v === null) ? (string) $v : '';

        $lookup = [];
        $orderedCols = [];
        foreach ($rowMap as $col => $val) {
            $key = strtolower(preg_replace('/\s+/', '', trim((string) $col)));
            if ($key === '' || isset($lookup[$key])) {
                continue;
            }
            $lookup[$key] = $col;
            $orderedCols[] = $col;
        }

        $tempDir = dirname($outputPath) . '/_temp_' . uniqid();
        $this->extractDocx($templatePath, $tempDir);

        $docXmlPath = $tempDir . '/word/document.xml';
        if (!file_exists($docXmlPath)) {
            throw new \RuntimeException('Format template DOCX tidak valid.');
        }

        $dom = new \DOMDocument();
        $dom->load($docXmlPath);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = $xpath->query('//w:p');
        foreach ($paragraphs as $para) {
            $textNodes = $xpath->query('.//w:t', $para);
            if ($textNodes->length === 0) continue;

            $fullText = '';
            foreach ($textNodes as $tn) {
                $fullText .= $tn->textContent;
            }

            if (!preg_match('/\{\{/', $fullText)) continue;

            $fullText = preg_replace_callback('/\{\{\s*data\s+(\d+)\s*\}\}/', function ($match) use ($orderedCols, $rowMap, $str) {
                $idx = (int)$match[1] - 1;
                $col = $orderedCols[$idx] ?? null;
                return $col !== null ? $str($rowMap[$col] ?? '') : $match[0];
            }, $fullText);

            $fullText = preg_replace_callback('/\{\{\s*([\w\/-]+(?:\s+[\w\/-]+)*)\s*\}\}/', function ($match) use ($lookup, $rowMap, $extraContext, $tagMapping, $str) {
                $tag = trim($match[1]);
                if (isset($extraContext[$tag])) {
                    return $str($extraContext[$tag]);
                }
                $key = strtolower(preg_replace('/\s+/', '', $tag));
                if (isset($tagMapping[$key]) && is_string($tagMapping[$key])) {
                    $mc = $tagMapping[$key];
                    if (array_key_exists($mc, $rowMap)) {
                        return $str($rowMap[$mc]);
                    }
                    $mk = strtolower(preg_replace('/\s+/', '', trim($mc)));
                    if (isset($lookup[$mk])) {
                        return $str($rowMap[$lookup[$mk]] ?? '');
                    }
                    return $match[0];
                }
                if (isset($lookup[$key])) {
                    return $str($rowMap[$lookup[$key]] ?? '');
                }
                return $match[0];
            }, $fullText);

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

    public function createRevisionPdf(string $filename, array $catatan, string $outputPath, string $subfolder = '(folder utama)', string $userNama = '-', string $adminNama = '-'): string
    {
        $tanggal = now()->format('d/m/Y H:i');
        $namaBerkas = $filename;
        $catatanList = $catatan;
        $pdf = app('dompdf.wrapper');
        $html = view('admin.revision-notification', compact('namaBerkas', 'tanggal', 'catatanList', 'subfolder', 'userNama', 'adminNama'))->render();
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
}
