<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EtiquetasConceptoTest extends TestCase
{
    public function test_las_etiquetas_requeridas_aparecen_en_el_codigo(): void
    {
        $totales = [];

        foreach (['app', 'database', 'resources/views', 'routes'] as $carpeta) {
            foreach (File::allFiles(base_path($carpeta)) as $archivo) {
                if ($archivo->getExtension() === 'php') {
                    preg_match_all('/\[([A-Z-]+)\]/', $archivo->getContents(), $coincidencias);
                    foreach ($coincidencias[1] as $etiqueta) {
                        $totales[$etiqueta] = ($totales[$etiqueta] ?? 0) + 1;
                    }
                }
            }
        }

        foreach ([
            'MIGRACION', 'RELACION', 'ENCAPSULAMIENTO', 'CRUD-READ', 'CRUD-CREATE', 'CRUD-UPDATE',
            'CRUD-DELETE', 'VALIDACION', 'REGLA-NEGOCIO', 'SEGURIDAD', 'INYECCION-DEPENDENCIAS',
        ] as $etiqueta) {
            $this->assertArrayHasKey($etiqueta, $totales, "Falta la etiqueta [$etiqueta].");
        }

        $this->assertGreaterThanOrEqual(5, $totales['REGLA-NEGOCIO']);
    }
}
