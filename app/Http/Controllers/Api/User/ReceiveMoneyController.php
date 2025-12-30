<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;

class ReceiveMoneyController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->createQr();
        $userQrCode = $user->qrCode()->first();
        $uniqueCode = $userQrCode->qr_code ?? '';
        $qrCode = generateQr($uniqueCode);
        $data = [
            'uniqueCode' => @$uniqueCode,
            'qrCode' => @$qrCode,
        ];

        $message = ['success' => [__('Receive Money')]];

        return Helpers::success($data, $message);

    }
}
