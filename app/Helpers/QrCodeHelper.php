<?php

namespace App\Helpers;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeHelper
{
    public static function generateQrCode($data, $size = 200)
    {
        try {
            $qrCode = new QrCode($data);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (\Exception $e) {
            return null;
        }
    }
}
