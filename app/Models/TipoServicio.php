<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoServicio extends Model
{
    // Laravel pluraliza en inglés y buscaría "tipo_servicios", que no existe.
    protected $table = 'tipos_servicio';

    // [ENCAPSULAMIENTO] Solo estas columnas se pueden asignar en masa.
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
    ];

    // [ENCAPSULAMIENTO] El dinero siempre se lee con dos decimales.
    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
        ];
    }

    // [RELACION] Un tipo de servicio tiene muchas órdenes de servicio.
    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }
}
