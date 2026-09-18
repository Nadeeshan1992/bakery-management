<?php
// includes/xlsx_helper.php - Lightweight pure-PHP XLSX and CSV spreadsheet reader

class SimpleXLSXHelper {
    /**
     * Parse an .xlsx or .csv file into an array of rows
     */
    public static function parse($filePath) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            return self::parseCSV($filePath);
        }

        return self::parseXLSX($filePath);
    }

    /**
     * Parse CSV file
     */
    public static function parseCSV($filePath) {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                // If row is not completely empty
                if (array_filter($data, function($v) { return trim($v) !== ''; })) {
                    $rows[] = array_map('trim', $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Parse XLSX file using ZipArchive and SimpleXML
     */
    public static function parseXLSX($filePath) {
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive PHP extension is required to read .xlsx files.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Could not open uploaded .xlsx file. Please verify it is a valid Excel spreadsheet.");
        }

        // 1. Parse Shared Strings table
        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xml = @simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
            if ($xml) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string)$val->t;
                    } elseif (isset($val->r)) {
                        $str = '';
                        foreach ($val->r as $r) {
                            $str .= (string)$r->t;
                        }
                        $sharedStrings[] = $str;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Parse Sheet 1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            throw new Exception("Unable to locate sheet1 inside the .xlsx spreadsheet.");
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $r = [];
            $colIndex = 0;
            foreach ($row->c as $c) {
                $cellRef = (string)$c['r']; // e.g. A1, B1, AA1
                preg_match('/([A-Z]+)(\d+)/', $cellRef, $matches);
                $colLetters = $matches[1] ?? '';

                $targetIndex = 0;
                $len = strlen($colLetters);
                for ($i = 0; $i < $len; $i++) {
                    $targetIndex = $targetIndex * 26 + (ord($colLetters[$i]) - ord('A') + 1);
                }
                $targetIndex -= 1;

                while ($colIndex < $targetIndex) {
                    $r[$colIndex++] = '';
                }

                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : '';

                if ($type === 's') {
                    $val = $sharedStrings[(int)$val] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string)$c->is->t;
                }

                $r[$colIndex++] = trim($val);
            }

            if (array_filter($r, function($v) { return trim($v) !== ''; })) {
                $rows[] = $r;
            }
        }

        return $rows;
    }
}
