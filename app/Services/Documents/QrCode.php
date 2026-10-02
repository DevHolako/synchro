<?php

namespace App\Services\Documents;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes for printed documents, as SVG (vector, so they stay sharp in print).
 */
final class QrCode
{
    /**
     * The QR code as a data URI, ready for an `<img>` in a PDF template.
     */
    public static function svgDataUri(string $content, int $size = 160): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($content);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
