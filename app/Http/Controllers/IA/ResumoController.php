<?php

declare(strict_types=1);

namespace App\Http\Controllers\IA;

use App\Domain\Service\GerarResumoService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumoController extends Controller
{
    public function __construct(
        private GerarResumoService $gerarResumoService
    ) {
    }


    public function gerarResumoComHistorico(Request $request): JsonResponse
    {
        $request->validate([
            'historico' => 'required|array',
            'formato' => 'sometimes|string|in:texto,html,markdown'
        ]);

        $historico = $request->input('historico');
        $formato = $request->input('formato', 'texto');

        $resultado = $this->gerarResumoService->gerarResumo($historico, $formato);

        if ($resultado->isSucesso()) {
            return response()->json([
                'resumo' => $resultado->getResumo(),
                'provedor' => $resultado->getProvedor()
            ], 200);
        }

        return response()->json([
            'error' => $resultado->getErro(),
            'provedor' => $resultado->getProvedor()
        ], 500);
    }
}
