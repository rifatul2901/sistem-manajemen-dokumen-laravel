<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
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

        $this->replacePlaceholdersInDocx($docXmlPath, function (string $full) use ($selectedData, $extraContext) {
            if (preg_match('/^\{\{\s*data\s+(\d+)\s*\}\}$/i', $full, $m)) {
                $idx = (int) $m[1] - 1;
                if (array_key_exists($idx, $selectedData) && $selectedData[$idx] !== null) {
                    return (string) $selectedData[$idx];
                }
                return null;
            }

            if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/s', $full, $m)) {
                $tag = trim($m[1]);
                if (isset($extraContext[$tag])) {
                    return (string) $extraContext[$tag];
                }
            }

            return null;
        });

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

        $this->replacePlaceholdersInDocx($docXmlPath, function (string $full) use ($orderedCols, $rowMap, $extraContext, $tagMapping, $lookup, $str) {
            if (preg_match('/^\{\{\s*data\s+(\d+)\s*\}\}$/i', $full, $m)) {
                $idx = (int) $m[1] - 1;
                $col = $orderedCols[$idx] ?? null;
                if ($col !== null) {
                    return $str($rowMap[$col] ?? '');
                }
            }

            if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/s', $full, $m)) {
                $tag = trim($m[1]);

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
                    return null;
                }

                if (isset($lookup[$key])) {
                    return $str($rowMap[$lookup[$key]] ?? '');
                }
            }

            return null;
        });

        $this->zipDocx($tempDir, $outputPath);
        $this->rrmdir($tempDir);

        $templateProcessor = new TemplateProcessor($outputPath);
        foreach ($extraContext as $key => $val) {
            $templateProcessor->setValue($key, $val);
        }
        $templateProcessor->saveAs($outputPath);
    }

    /**
     * Mengganti placeholder langsung di dalam text run asli agar format
     * (font, bold, italic, warna, dsb.) milik template tidak hilang.
     */
    protected function replacePlaceholdersInDocx(string $docXmlPath, callable $resolver): void
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->encoding = 'UTF-8';

        if (!$dom->load($docXmlPath, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Format template DOCX tidak valid.');
        }
        $dom->xmlStandalone = true;

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') as $para) {
            $textNodes = [];
            foreach ($xpath->query('.//w:t', $para) as $tn) {
                $textNodes[] = $tn;
            }
            if (!$textNodes) {
                continue;
            }

            $text = '';
            foreach ($textNodes as $tn) {
                $text .= $tn->textContent;
            }
            if (strpos($text, '{{') === false) {
                continue;
            }

            $this->applyReplacementsToParagraph($textNodes, $text, $resolver);
        }

        $dom->save($docXmlPath);
    }

    /**
     * Menyisipkan nilai hanya pada rentang karakter placeholder, tanpa
     * menggabungkan atau menghapus text run lain di paragraf yang sama.
     *
     * @param  \DOMElement[]  $textNodes
     */
    protected function applyReplacementsToParagraph(array $textNodes, string $text, callable $resolver): bool
    {
        if (!preg_match_all('/\{\{\s*[^{}]*?\s*\}\}/u', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $ranges = [];
        $offset = 0;
        foreach ($textNodes as $tn) {
            $len = strlen($tn->textContent);
            $ranges[] = [$offset, $offset + $len];
            $offset += $len;
        }

        $changed = false;

        // Proses dari belakang agar offset kecocokan sebelumnya tidak bergeser.
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            $full = $matches[0][$i][0];
            $start = $matches[0][$i][1];
            $end = $start + strlen($full);
            if ($start < 0 || $end <= $start) {
                continue;
            }

            $replacement = $resolver($full);
            if ($replacement === null) {
                continue;
            }
            $replacement = (string) $replacement;

            $startIdx = null;
            $endIdx = null;
            $startOffset = 0;
            $endOffset = 0;

            foreach ($ranges as $idx => $range) {
                if ($startIdx === null && $start >= $range[0] && $start < $range[1]) {
                    $startIdx = $idx;
                    $startOffset = $start - $range[0];
                }
                if ($endIdx === null && $end > $range[0] && $end <= $range[1]) {
                    $endIdx = $idx;
                    $endOffset = $end - $range[0];
                }
            }

            if ($startIdx === null || $endIdx === null) {
                continue;
            }

            $startText = $textNodes[$startIdx]->textContent;

            if ($startIdx === $endIdx) {
                $textNodes[$startIdx]->nodeValue = substr($startText, 0, $startOffset)
                    . $replacement
                    . substr($startText, $endOffset);
            } else {
                $textNodes[$startIdx]->nodeValue = substr($startText, 0, $startOffset) . $replacement;
                for ($k = $startIdx + 1; $k < $endIdx; $k++) {
                    if ($textNodes[$k]->textContent !== '') {
                        $textNodes[$k]->nodeValue = '';
                    }
                }
                $endText = $textNodes[$endIdx]->textContent;
                $textNodes[$endIdx]->nodeValue = substr($endText, $endOffset);
            }

            $changed = true;
        }

        if ($changed) {
            foreach ($textNodes as $tn) {
                $tn->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            }
        }

        return $changed;
    }

    public function convertDocxToPdf(string $docxPath, string $pdfPath): void
    {
        if (!file_exists($docxPath)) {
            throw new \RuntimeException('File DOCX tidak ditemukan.');
        }

        if (DIRECTORY_SEPARATOR === '\\' && $this->convertWithWord($docxPath, $pdfPath)) {
            return;
        }

        Log::warning('Konversi PDF memakai DomPDF; tata letak bisa berbeda dari DOCX.', [
            'file' => basename($docxPath),
        ]);

        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);

        $phpWord = IOFactory::load($docxPath);
        $xmlWriter = IOFactory::createWriter($phpWord, 'PDF');
        $xmlWriter->save($pdfPath);
    }

    /**
     * Konversi DOCX ke PDF memakai Microsoft Word (COM) agar font, tabel,
     * pembungkusan baris, dan margin tetap sama seperti dokumen aslinya.
     */
    protected function convertWithWord(string $docxPath, string $pdfPath): bool
    {
        $powershell = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
        if (!is_file($powershell)) {
            return false;
        }

        $docxPath = realpath($docxPath) ?: $docxPath;
        $pdfDir = realpath(dirname($pdfPath)) ?: dirname($pdfPath);
        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . basename($pdfPath);
        $docxPath = str_replace('/', '\\', $docxPath);
        $pdfPath = str_replace('/', '\\', $pdfPath);

        $scriptPath = sys_get_temp_dir() . '/docx2pdf_' . uniqid() . '.ps1';
        $script = <<<'PS'
param(
    [Parameter(Mandatory=$true)][string]$In,
    [Parameter(Mandatory=$true)][string]$Out
)

$ErrorActionPreference = 'Stop'
$word = $null
$doc = $null
$code = 1

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $word.Options.DoNotPromptForConvert = $true

    $doc = $word.Documents.Open($In, $false, $true, $false)
    $doc.SaveAs2($Out, 17)
    $doc.Close($false)
    $doc = $null
    $code = 0
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
} finally {
    if ($doc -ne $null) {
        try { $doc.Close($false) } catch { }
    }
    if ($word -ne $null) {
        try { $word.Quit() } catch { }
    }
}

exit $code
PS;

        if (file_put_contents($scriptPath, $script) === false) {
            return false;
        }

        $command = [
            $powershell, '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass',
            '-File', $scriptPath, '-In', $docxPath, '-Out', $pdfPath,
        ];

        $process = @proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            @unlink($scriptPath);
            return false;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $started = microtime(true);
        $exitCode = null;
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = $status['exitcode'];
                break;
            }
            if (microtime(true) - $started > 60) {
                $timedOut = true;
                proc_terminate($process, 9);
                break;
            }
            usleep(100000);
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        @unlink($scriptPath);

        if ($timedOut) {
            @unlink($pdfPath);
            Log::warning('Konversi PDF via Word melebihi batas waktu.');
            return false;
        }

        clearstatcache(true, $pdfPath);
        $ok = $exitCode === 0
            && is_file($pdfPath)
            && filesize($pdfPath) > 0
            && file_get_contents($pdfPath, false, null, 0, 5) === '%PDF-';

        if (!$ok) {
            @unlink($pdfPath);
            Log::warning('Konversi PDF via Word gagal.', ['error' => trim($stderr)]);
        }

        return $ok;
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
