<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudBodegaPrimaria extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'solicitudes_bodegas_primarias';
    protected $hidden = ['created_at', 'updated_at'];

    public function bodega_primaria(){
        return $this->hasOne(Institucion::class,'id','id_bodega_primaria');
    }

    public function socio(){
        return $this->hasOne(Institucion::class,'id','id_socio_solicitante');
    }

    public function detalles(){
        return $this->hasMany(SolicitudBodegaPrimariaDetalle::class,'id_solicitud_bodega_primaria','id');
    }

}
