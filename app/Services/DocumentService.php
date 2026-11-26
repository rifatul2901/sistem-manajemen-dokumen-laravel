<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
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

        $this->normalizeDocxTypography($outputPath);
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

        $this->normalizeDocxTypography($outputPath);
    }

    /**
     * Menormalkan tipografi DOCX hasil generate: font eksplisit dan spasi
     * paragraf kosong. Dipanggil pada DOCX final (bukan saat konversi PDF)
     * agar berkas yang diunduh pengguna ikut ternormalisasi.
     *
     * Font ditulis eksplisit supaya Linux tidak melakukan substitusi font
     * (mis. Times New Roman -> Liberation Serif) yang menggeser metrik teks.
     * Nilai 320 twips (16pt) adalah hasil kalibrasi: mengganti paragraf
     * kosong dengan spacing tetap menjaga posisi tidak bergeser lebih dari
     * ~9pt dibanding tampilan asli. Template asli tidak diubah karena semua
     * operasi hanya dilakukan pada salinan hasil generate.
     *
     * @param string $docxPath Path DOCX hasil generate yang dinormalisasi.
     */
    protected function normalizeDocxTypography(string $docxPath): void
    {
        $font = 'Times New Roman';
        $twipsPerEmptyParagraph = 320; // 16pt
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        $workDir = dirname($docxPath) . '/_norm_' . uniqid();

        try {
            $this->extractDocx($docxPath, $workDir);

            // 1. Font eksplisit di docDefaults agar run tanpa rFonts ikut terbawa.
            $stylesPath = $workDir . '/word/styles.xml';
            if (file_exists($stylesPath)) {
                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = true;
                $dom->formatOutput = false;
                $dom->encoding = 'UTF-8';

                if ($dom->load($stylesPath, LIBXML_NONET | LIBXML_COMPACT)) {
                    $dom->xmlStandalone = true;

                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('w', $ns);

                    $rPrDefault = $xpath->query('//w:docDefaults/w:rPrDefault')->item(0);
                    if (!$rPrDefault) {
                        $docDefaults = $xpath->query('//w:docDefaults')->item(0);
                        if (!$docDefaults) {
                            $stylesRoot = $xpath->query('/w:styles')->item(0);
                            if ($stylesRoot) {
                                $docDefaults = $dom->createElementNS($ns, 'w:docDefaults');
                                $stylesRoot->insertBefore($docDefaults, $stylesRoot->firstChild);
                            }
                        }
                        if ($docDefaults) {
                            $rPrDefault = $dom->createElementNS($ns, 'w:rPrDefault');
                            $docDefaults->appendChild($rPrDefault);
                        }
                    }

                    if ($rPrDefault) {
                        // rFonts harus berada di dalam w:rPr sesuai skema OOXML.
                        $rPr = $xpath->query('./w:rPr', $rPrDefault)->item(0);
                        if (!$rPr) {
                            $rPr = $dom->createElementNS($ns, 'w:rPr');
                            $rPrDefault->insertBefore($rPr, $rPrDefault->firstChild);
                        }

                        $fonts = $xpath->query('./w:rFonts', $rPr)->item(0);
                        if (!$fonts) {
                            $fonts = $dom->createElementNS($ns, 'w:rFonts');
                            $rPr->insertBefore($fonts, $rPr->firstChild);
                        }

                        foreach (['ascii', 'hAnsi', 'eastAsia', 'cs'] as $attr) {
                            $fonts->setAttributeNS($ns, 'w:' . $attr, $font);
                        }

                        $dom->save($stylesPath);
                    }
                }
            }

            // 2. Font eksplisit pada run berteks + paragraf kosong -> spacing.
            $docPath = $workDir . '/word/document.xml';
            if (!file_exists($docPath)) {
                throw new \RuntimeException('Format DOCX hasil generate tidak valid.');
            }

            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = false;
            $dom->encoding = 'UTF-8';

            if (!$dom->load($docPath, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new \RuntimeException('Format DOCX hasil generate tidak valid.');
            }
            $dom->xmlStandalone = true;

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', $ns);

            // Jangan sentuh w:sz, bold, italic, warna, atau properti lain.
            // Snapshot dulu karena penyisipan w:rPr mengubah pohon DOM.
            $runs = [];
            foreach ($xpath->query('//w:r') as $run) {
                $runs[] = $run;
            }
            foreach ($runs as $run) {
                if ($xpath->query('.//w:t', $run)->length === 0) {
                    continue;
                }

                $rPr = $xpath->query('./w:rPr', $run)->item(0);
                if (!$rPr) {
                    $rPr = $dom->createElementNS($ns, 'w:rPr');
                    $run->insertBefore($rPr, $run->firstChild);
                }

                $fonts = $xpath->query('./w:rFonts', $rPr)->item(0);
                if (!$fonts) {
                    $fonts = $dom->createElementNS($ns, 'w:rFonts');
                    $rPr->insertBefore($fonts, $rPr->firstChild);
                }

                foreach (['ascii', 'hAnsi', 'eastAsia', 'cs'] as $attr) {
                    $fonts->setAttributeNS($ns, 'w:' . $attr, $font);
                }
            }

            // Paragraf kosong "murni" = tanpa teks, break, gambar, border,
            // shading, penomoran, atau sectPr. Paragraf kosong bermakna
            // (mis. bergaris bawah atau berisi page break) tetap dipertahankan.
            $isBlank = function (\DOMNode $node) use ($xpath): bool {
                foreach ($xpath->query('.//w:t', $node) as $text) {
                    if (trim($text->textContent) !== '') {
                        return false;
                    }
                }

                foreach (['br', 'cr', 'tab', 'drawing', 'pict', 'fldSimple', 'instrText', 'delText', 'hyperlink', 'object', 'sym'] as $tag) {
                    if ($xpath->query('.//w:' . $tag, $node)->length > 0) {
                        return false;
                    }
                }

                $pPr = $xpath->query('./w:pPr', $node)->item(0);
                if ($pPr && $xpath->query('./w:pBdr | ./w:shd | ./w:numPr | ./w:sectPr', $pPr)->length > 0) {
                    return false;
                }

                return true;
            };

            // Tambahkan ke nilai lama, jangan menimpa spacing yang sudah ada.
            $addSpacing = function (\DOMElement $paragraph, int $twips, string $side) use ($dom, $xpath, $ns): void {
                if ($twips <= 0) {
                    return;
                }

                $pPr = $xpath->query('./w:pPr', $paragraph)->item(0);
                if (!$pPr) {
                    $pPr = $dom->createElementNS($ns, 'w:pPr');
                    $paragraph->insertBefore($pPr, $paragraph->firstChild);
                }

                $spacing = $xpath->query('./w:spacing', $pPr)->item(0);
                if (!$spacing) {
                    $spacing = $dom->createElementNS($ns, 'w:spacing');

                    // Urutan CT_PPrBase: w:spacing harus mendahului w:ind,
                    // w:jc, w:rPr, dan turunannya agar dokumen tetap valid.
                    $afterSpacing = [
                        'ind', 'contextualSpacing', 'mirrorIndents', 'suppressOverlap', 'jc',
                        'textDirection', 'textAlignment', 'textboxTightWrap', 'outlineLvl',
                        'divId', 'cnfStyle', 'rPr', 'sectPr', 'pPrChange',
                    ];
                    $anchor = null;
                    foreach ($pPr->childNodes as $child) {
                        if ($child instanceof \DOMElement && in_array($child->localName, $afterSpacing, true)) {
                            $anchor = $child;
                            break;
                        }
                    }
                    if ($anchor instanceof \DOMElement) {
                        $pPr->insertBefore($spacing, $anchor);
                    } else {
                        $pPr->appendChild($spacing);
                    }
                }

                $current = (int) $spacing->getAttributeNS($ns, $side);
                $spacing->setAttributeNS($ns, 'w:' . $side, (string) ($current + $twips));
            };

            $body = $xpath->query('//w:body')->item(0);
            if ($body) {
                // childNodes bersifat live: snapshot dulu agar removeChild
                // saat iterasi tidak melewati node berikutnya.
                $children = [];
                foreach ($body->childNodes as $child) {
                    $children[] = $child;
                }

                $pending = 0;
                $lastContent = null;
                $lastBlank = null;

                foreach ($children as $node) {
                    if (!$node instanceof \DOMElement || $node->localName !== 'p' || $node->namespaceURI !== $ns) {
                        continue;
                    }

                    if ($isBlank($node)) {
                        $pending += $twipsPerEmptyParagraph;
                        $lastBlank = $node;
                        $body->removeChild($node);
                        continue;
                    }

                    if ($pending > 0) {
                        // Ditaruh sebagai "before" pada paragraf berikutnya agar
                        // aman untuk paragraf pertama (tanpa paragraf sebelumnya).
                        $addSpacing($node, $pending, 'before');
                        $pending = 0;
                    }

                    $lastContent = $node;
                }

                if ($pending > 0 && $lastContent instanceof \DOMElement) {
                    $addSpacing($lastContent, $pending, 'after');
                } elseif ($pending > 0 && $lastBlank instanceof \DOMElement) {
                    // Dokumen yang hanya berisi paragraf kosong tetap
                    // menyimpan satu paragraf agar tidak menjadi kosong.
                    // Paragraf harus ditempatkan sebelum w:sectPr di akhir body.
                    $sectPr = $xpath->query('./w:sectPr', $body)->item(0);
                    if ($sectPr instanceof \DOMElement) {
                        $body->insertBefore($lastBlank, $sectPr);
                    } else {
                        $body->appendChild($lastBlank);
                    }
                    $addSpacing($lastBlank, $pending, 'before');
                }
            }

            $dom->save($docPath);

            $this->zipDocx($workDir, $docxPath);
        } finally {
            $this->rrmdir($workDir);
        }
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

        // Di Linux, konversi langsung dari DOCX jauh lebih mirip Word
        // (ukuran kertas, spasi, font, dan posisi tabel mengikuti template).
        // DomPDF tetap dipakai jika LibreOffice tidak tersedia di hosting.
        if ($this->convertWithLibreOffice($docxPath, $pdfPath)) {
            return;
        }

        Log::warning('Konversi PDF memakai DomPDF; tata letak bisa berbeda dari DOCX.', [
            'file' => basename($docxPath),
        ]);

        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);

        $phpWord = IOFactory::load($docxPath);
        $writer = IOFactory::createWriter($phpWord, 'PDF');

        // Writer PDF PhpWord memakai HTML perantara dengan CSS kaku
        // (A4 hardcode, paragraf kosong = &nbsp;, tabel bisa terpotong).
        // HTML-nya dirapikan dulu sebelum diserahkan ke DomPDF.
        $html = $this->preparePdfHtml($docxPath, $writer->getContent());

        $page = $this->readPageSetupFromDocx($docxPath);

        $pdf = new Dompdf(new Options());
        $pdf->setPaper([$page[0], $page[1], $page[2], $page[3]], 'portrait');
        $pdf->loadHtml($html);
        $pdf->render();

        if (file_put_contents($pdfPath, $pdf->output()) === false) {
            throw new \RuntimeException('Gagal menulis file PDF hasil konversi.');
        }
    }

    /**
     * Konversi DOCX -> PDF memakai LibreOffice headless.
     */
    protected function convertWithLibreOffice(string $docxPath, string $pdfPath): bool
    {
        $bin = '/usr/bin/soffice';
        if (!is_executable($bin)) {
            return false;
        }

        $workDir = sys_get_temp_dir() . '/docx2pdf_' . uniqid();
        if (!mkdir($workDir, 0777, true)) {
            return false;
        }

        // Nama file disederhanakan agar nama hasil konversi bisa diprediksi.
        $localDocx = $workDir . '/input.docx';
        if (!copy($docxPath, $localDocx)) {
            $this->rrmdir($workDir);

            return false;
        }

        $command = 'timeout 90 ' . escapeshellarg($bin)
            . ' --headless --norestore --nologo --nodefault --nolockcheck'
            . ' -env:UserInstallation=file://' . escapeshellarg($workDir . '/profile')
            . ' --convert-to pdf:writer_pdf_Export --outdir ' . escapeshellarg($workDir)
            . ' ' . escapeshellarg($localDocx) . ' 2>&1';

        exec($command, $output, $exitCode);

        $generated = $workDir . '/input.pdf';
        $ok = $exitCode === 0 && file_exists($generated) && copy($generated, $pdfPath);

        $this->rrmdir($workDir);

        return $ok;
    }

    /**
     * Pengaturan halaman untuk PDF. Defaultnya A4; ukuran eksplisit dari
     * template dipakai hanya jika bukan ukuran bawaan Word (Letter
     * 12240x15840 twips), karena Letter lebih pendek dan membuat
     * tabel/catatan terdorong ke halaman berikutnya.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float, 7: float}
     */
    protected function readPageSetupFromDocx(string $docxPath): array
    {
        $width = 595.28;
        $height = 841.89;
        $margins = [72.0, 72.0, 72.0, 72.0]; // top, right, bottom, left

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if (is_string($xml)) {
                if (preg_match('/<w:pgSz\b[^>]*\bw:w="(\d+)"[^>]*\bw:h="(\d+)"/', $xml, $m)) {
                    $twipsWidth = (int) $m[1];
                    $twipsHeight = (int) $m[2];

                    if ($twipsWidth !== 12240 || $twipsHeight !== 15840) {
                        $width = round($twipsWidth / 20, 2);
                        $height = round($twipsHeight / 20, 2);
                    }
                }

                if (preg_match('/<w:pgMar\b([^>]*)\/>/', $xml, $m)) {
                    foreach (['top', 'right', 'bottom', 'left'] as $i => $side) {
                        if (preg_match('/\bw:' . $side . '="(-?\d+)"/', $m[1], $sm)) {
                            $margins[$i] = round(((int) $sm[1]) / 20, 2);
                        }
                    }
                }
            }
        }

        return array_merge([0, 0, $width, $height], $margins);
    }

    /**
     * Merapikan HTML perantara PhpWord agar DomPDF tidak menghasilkan
     * baris kosong berlebih, tabel terpotong, atau label menggantung.
     */
    protected function preparePdfHtml(string $docxPath, string $html): string
    {
        // Paragraf kosong dari template dipadatkan, bukan dihapus, agar jarak
        // antar bagian tetap terjaga tanpa mendorong tabel ke halaman berikutnya.
        $html = str_replace('<p>&nbsp;</p>', '<p class="spacer"></p>', $html);
        $html = str_replace('<p></p>', '<p class="spacer"></p>', $html);

        // Label pendek seperti "Catatan:" tidak boleh berdiri sendiri di
        // bawah halaman, terpisah dari paragraf penjelasnya.
        $html = preg_replace_callback(
            '/<p\b([^>]*)>\s*(<span\b[^>]*>([^<]{1,60}:)<\/span>)\s*<\/p>/u',
            static function (array $matches): string {
                $attributes = $matches[1];
                if (preg_match('/\bstyle="([^"]*)"/', $attributes, $style)) {
                    // DomPDF mengabaikan page-break-after bila margin-top
                    // eksplisit mendorong label ke bawah halaman. Untuk label
                    // pendek ini, margin-top dinetralisir di jalur HTML saja
                    // (jalur LibreOffice tidak tersentuh).
                    $existing = preg_replace('/(?:^|;)\s*margin-top\s*:\s*[^;]*/', '', $style[1]);
                    $existing = trim(str_replace(';;', ';', $existing), " \t\n\r\0\x0B;");
                    $newStyle = $existing === ''
                        ? 'page-break-after: avoid;'
                        : $existing . '; page-break-after: avoid;';
                    $attributes = str_replace($style[0], 'style="' . $newStyle . '"', $attributes);
                } else {
                    $attributes .= ' style="page-break-after: avoid;"';
                }

                return '<p' . $attributes . '>' . $matches[2] . '</p>';
            },
            $html
        ) ?? $html;

        $page = $this->readPageSetupFromDocx($docxPath);
        [$width, $height, $top, $right, $bottom, $left] = [$page[2], $page[3], $page[4], $page[5], $page[6], $page[7]];

        $css = <<<CSS
        <style>
        @page {size: {$width}pt {$height}pt; margin: {$top}pt {$right}pt {$bottom}pt {$left}pt;}
        .spacer {height: 2pt; line-height: 0; margin: 0; padding: 0; font-size: 1pt;}
        p {orphans: 2; widows: 2; white-space: normal;}
        h1, h2, h3, h4, h5, h6 {page-break-after: avoid;}
        table, tr {page-break-inside: avoid;}
        </style>
        CSS;

        // CSS ini ditaruh paling akhir supaya mengalahkan CSS bawaan PhpWord.
        $pos = strrpos($html, '</head>');
        if ($pos !== false) {
            return substr($html, 0, $pos) . $css . substr($html, $pos);
        }

        return $css . $html;
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
