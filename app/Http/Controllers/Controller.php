<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected static function sanitizeFilename(string $filename): string
    {
        $filename = pathinfo($filename, PATHINFO_BASENAME);
        $filename = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);
        return trim($filename);
    }
}
