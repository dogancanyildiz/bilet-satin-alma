<?php
/**
 * Simple PDF generator utilities for ticket export
 */

function pdf_encode_text(string $text): string {
    $converted = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $text);
    if ($converted === false) {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
    if ($converted === false) {
        $converted = preg_replace('/[^\x20-\x7E]/', '?', $text);
    }
    $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
    return $escaped;
}

function build_simple_pdf(array $lines): string {
    $stream = "BT\n/F1 14 Tf\n";
    $firstLine = true;
    foreach ($lines as $line) {
        $escaped = pdf_encode_text($line);
        if ($firstLine) {
            $stream .= "50 780 Td\n($escaped) Tj\n";
            $firstLine = false;
        } else {
            $stream .= "0 -22 Td\n($escaped) Tj\n";
        }
    }
    $stream .= "ET";

    $content = "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream";

    $objects = [
        "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj",
        "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj",
        "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj",
        "4 0 obj $content endobj",
        "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj . "\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n$xrefOffset\n%%EOF";

    return $pdf;
}

function format_ticket_datetime(?string $value): string {
    if (!$value) {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dt) {
        return $value;
    }
    return $dt->format('d.m.Y H:i');
}

function create_ticket_pdf(array $ticket, array $owner): string {
    $lines = [];
    $lines[] = 'Bilet Satın Alma Platformu';
    $lines[] = '------------------------------';
    $lines[] = 'Bilet No: ' . ($ticket['id'] ?? '-');
    $lines[] = 'Yolcu: ' . ($ticket['passenger_name'] ?: ($owner['name'] ?? ''));
    $lines[] = ' Firma: ' . ($ticket['company_name'] ?? '-');
    $lines[] = ' Kalkış: ' . ($ticket['departure_city'] ?? '-') . ' - ' . format_ticket_datetime($ticket['departure_time'] ?? null);
    $lines[] = ' Varış: ' . ($ticket['arrival_city'] ?? '-') . ' - ' . format_ticket_datetime($ticket['arrival_time'] ?? null);
    $lines[] = ' Koltuk: ' . ($ticket['seat_number'] ?? '-');
    $lines[] = ' Toplam Ücret: ' . number_format((float)($ticket['total_price'] ?? 0), 2) . ' TL';
    if (!empty($ticket['discount_amount']) && (float)$ticket['discount_amount'] > 0) {
        $lines[] = ' İndirim: ' . number_format((float)$ticket['discount_amount'], 2) . ' TL';
    }
    if (!empty($ticket['coupon_code'])) {
        $lines[] = ' Kupon: ' . $ticket['coupon_code'];
    }
    $lines[] = ' Satın Alma Tarihi: ' . format_ticket_datetime($ticket['created_at'] ?? null);
    $lines[] = ' Durum: ' . ($ticket['status'] ?? '-');
    $lines[] = '------------------------------';
    $lines[] = 'İyi yolculuklar dileriz.';

    return build_simple_pdf($lines);
}

function output_ticket_pdf(array $ticket, array $owner) {
    $pdfContent = create_ticket_pdf($ticket, $owner);
    $filename = 'bilet-' . ($ticket['id'] ?? 'bilgi') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: no-store');

    echo $pdfContent;
    exit;
}
