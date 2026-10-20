<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\HttpFoundation\Response;

class HorseQrController extends Controller
{
    public function __invoke(Horse $horse): Response
    {
        abort_unless($horse->stable_id !== null && $horse->stable_id === auth()->user()->stable_id, 404);
        $code = new QrCode(data: route('horses.show', $horse), size: 320, margin: 10);
        $result = (new SvgWriter())->write($code);
        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="stablecare-zirgs-'.$horse->id.'-qr.svg"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
