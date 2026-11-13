<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// [VALIDACION] Período del reporte y de la exportación a CSV. Las dos fechas son opcionales;
// sin ninguna, el controlador usa el mes actual.
class FiltroReporteRequest extends FormRequest
{
    // El reporte es público, igual que el resto de las páginas de lectura.
    public function authorize(): bool
    {
        return true;
    }

    // Al fallar se vuelve al reporte sin las fechas inválidas en la URL.
    protected $redirectRoute = 'reportes.index';

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date'],
            // after_or_equal solo se aplica si hay fecha inicial: sin ella no hay con qué comparar.
            'hasta' => array_merge(
                ['nullable', 'date'],
                $this->filled('desde') ? ['after_or_equal:desde'] : []
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date' => 'La fecha inicial no es una fecha válida.',
            'hasta.date' => 'La fecha final no es una fecha válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'desde' => 'fecha inicial',
            'hasta' => 'fecha final',
        ];
    }

    /**
     * Período a consultar. Sin ninguna fecha, el mes actual completo.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function periodo(): array
    {
        $desde = $this->validated('desde');
        $hasta = $this->validated('hasta');

        if (! $desde && ! $hasta) {
            return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        }

        return [$desde, $hasta];
    }
}
