<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroReporteRequest;
use App\Http\Requests\HistorialVehiculoRequest;
use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Reporte del caso (Carlos): pendientes e ingresos (C8), historial por vehículo (C9)
// y filtro por fechas con exportación a CSV (C10).
class ReporteController extends Controller
{
    // [INYECCION-DEPENDENCIAS] Laravel crea y valida FiltroReporteRequest antes de llamar al método.
    // [CRUD-READ] Página del reporte. Las sumas y los conteos los calcula la base de datos.
    public function index(FiltroReporteRequest $request)
    {
        // Sin fechas, el período es el mes actual.
        [$desde, $hasta] = $request->periodo();

        // Órdenes que siguen en el taller, de la más antigua a la más reciente.
        // with() carga vehículo y servicio en la misma tanda, sin una consulta por fila.
        $pendientes = OrdenServicio::pendientes()
            ->delPeriodo($desde, $hasta)
            ->with(['vehiculo', 'tipoServicio'])
            ->orderBy('fecha_ingreso')
            ->orderBy('id')
            ->get()
            ->each(fn (OrdenServicio $orden) => $orden->setAttribute(
                'dias_en_taller',
                (int) $orden->fecha_ingreso->diffInDays(today())
            ));

        $ingresos = $this->ingresosPorServicio($desde, $hasta);

        $totalGeneral = (float) OrdenServicio::estado('finalizada')
            ->delPeriodo($desde, $hasta)
            ->sum('costo_total');

        return view('reportes.index', compact('pendientes', 'ingresos', 'totalGeneral', 'desde', 'hasta'));
    }

    // [INYECCION-DEPENDENCIAS] Laravel crea y valida HistorialVehiculoRequest antes de llamar al método.
    // [CRUD-READ] Historial de servicios de un vehículo, con total gastado y último kilometraje.
    public function historial(HistorialVehiculoRequest $request)
    {
        $vehiculos = Vehiculo::orderBy('placa')->get(['id', 'placa', 'propietario_nombre']);

        $vehiculo = null;
        $totalGastado = 0.0;
        $ultimoKilometraje = null;

        if ($id = $request->validated('vehiculo_id')) {
            $vehiculo = Vehiculo::findOrFail($id)->load([
                'ordenes' => fn ($q) => $q->orderByDesc('fecha_ingreso')->orderByDesc('id'),
                'ordenes.tipoServicio',
            ]);
            // Métodos de dominio del modelo (C4): una sola fuente de verdad para la ficha y el reporte.
            $totalGastado = $vehiculo->totalGastado();
            $ultimoKilometraje = $vehiculo->ultimoKilometraje();
        }

        return view('reportes.historial', compact('vehiculos', 'vehiculo', 'totalGastado', 'ultimoKilometraje'));
    }

    // Exporta los ingresos por servicio del mismo período a CSV, sin paquetes externos (extra +3).
    public function exportar(FiltroReporteRequest $request): StreamedResponse
    {
        [$desde, $hasta] = $request->periodo();
        $ingresos = $this->ingresosPorServicio($desde, $hasta);

        return response()->streamDownload(function () use ($ingresos) {
            $salida = fopen('php://output', 'w');
            // BOM UTF-8: Excel lo necesita para mostrar las tildes.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Servicio', 'Órdenes', 'Ingresos'], escape: '\\');

            foreach ($ingresos as $tipo) {
                fputcsv($salida, [
                    $tipo->nombre,
                    $tipo->ordenes_finalizadas,
                    number_format((float) $tipo->ingresos, 2, '.', ''),
                ], escape: '\\');
            }

            fclose($salida);
        }, 'ingresos-por-servicio.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Ingresos por tipo de servicio en el período: solo cuentan las órdenes finalizadas.
    // La usan la página y el CSV, así los dos muestran siempre los mismos números.
    private function ingresosPorServicio(?string $desde, ?string $hasta): Collection
    {
        $finalizadasDelPeriodo = fn ($q) => $q->estado('finalizada')->delPeriodo($desde, $hasta);

        return TipoServicio::query()
            ->withCount(['ordenes as ordenes_finalizadas' => $finalizadasDelPeriodo])
            ->withSum(['ordenes as ingresos' => $finalizadasDelPeriodo], 'costo_total')
            ->orderByDesc('ingresos')
            ->orderBy('nombre')
            ->get();
    }
}
