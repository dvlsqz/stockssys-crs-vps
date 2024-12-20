<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Solicitud, App\Models\SolicitudDetalles,App\Models\Institucion, App\Models\Bodega, App\Models\Escuela, App\Models\Entrega;
use App\Models\Ruta, App\Models\RutaEscuela,  App\Models\RutaSolicitud,  App\Models\RutaSolicitudDetalles,  App\Models\Racion, App\Models\Kit, App\Models\BodegaEgreso, App\Models\BodegaEgresoDetalle;
use App\Models\BodegaIngreso, App\Models\BodegaIngresoDetalle;
use App\Models\AlimentoRacion, App\Models\InsumoKit,App\Models\Usuario, App\Models\Bitacora, App\Models\SolicitudBodegaPrimaria, App\Models\SolicitudBodegaPrimariaDetalle;
use DB, Validator, Auth, Hash, Config, Carbon\Carbon, Illuminate\Support\Arr;
use App\Imports\SolicitudDetallesImport;
use App\Exports\GuiaTerrestreExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Elibyy\TCPDF\Facades\TCPDF;


class SolicitudController extends Controller
{
    public function getInicio(){
        if(Auth::user()->rol == 0 || Auth::user()->rol == 1 ):
            $solicitudes = Solicitud::with(['entrega', 'usuario'])->get();
        else:
            $solicitudes = Solicitud::with(['entrega', 'usuario'])->where('id_socio',  Auth::user()->id_institucion)->get();
        endif;
        

        $datos = [
            'solicitudes' => $solicitudes
        ];

        return view('admin.solicitudes.inicio',$datos);
    }

    public function getSolicitudRegistrar(){
        $entregas = Entrega::get(); 

        $datos = [
            'entregas' => $entregas
        ];

        return view('admin.solicitudes.registrar',$datos);
    }

    public function postSolicitudRegistrar(Request $request){
        $s = new Solicitud;
        $s->id_entrega = $request->input('idEntrega');
        $s->id_usuario = $request->input('idUsuario');
        $s->observaciones = $request->input('observaciones');
        $s->tipo_insumos = $request->input('tipo_insumos');
        $s->id_socio = Auth::user()->id_institucion;
        $s->save();

        $idSolicitud = $s->id;

        $archivo = request()->file('datos');
        $solicitud = Excel::toArray(new SolicitudDetallesImport, $archivo);

        foreach($solicitud[0] as $csd):
            

            $reglas = [
                'fecha_de_solicitud' => 'required',
                'codigo_de_la_escuela' => 'required|string',
                'mes_de_solicitud' => 'required|string',
                'dias_de_solicitud' => 'required|integer',
                'ninas_pre_primaria_a_tercero_primaria' => 'required|integer',
                'ninos_pre_primaria_a_tercero_primaria' => 'required|integer',
                'total_pre_primaria_a_tercero_primaria' => 'required|integer',
                'ninas_cuarto_a_sexto' => 'required|integer',
                'ninios_cuarto_sexto' => 'required|integer',
                'total_cuarto_a_sexto' => 'required|integer',
                'total_de_estudiantes' => 'required|integer',
                'total_de_raciones_de_estudiantes' => 'required|integer',
                'total_docentes' => 'required|integer',
                'total_voluntarios' => 'required|integer',
                'total_de_docentes_y_voluntarios' => 'required|integer',
                'total_de_raciones_de_docentes_y_voluntarios' => 'required|integer',
                'total_de_personas' => 'required|integer',
                'total_de_raciones' => 'required|integer',
                'tipo_de_actividad_alimentos' => 'required|string',
                'numero_de_entrega' => 'required|integer',
                'tipo' => 'required|string',
            ];
            $mensajes = [
                'fecha_de_solicitud.required' => 'Se requiere una fecha de solicitud para la escuela.',              
                'codigo_de_la_escuela.required' => 'Se requiere un codigo para la escuela.',
                'codigo_de_la_escuela.string' => 'El codigo debe ser un dato numerico incluyendo los guiones.',
                'mes_de_solicitud.required' => 'Se requiere un mes de solicitud para la escuela.',
                'mes_de_solicitud.string' => 'El mes debe ser un dato texto.',
                'dias_de_solicitud.required' => 'Se requiere los dias a cubrir en la solicitud para la escuela.',
                'dias_de_solicitud.integer' => 'El dia debe ser un dato numerico.',
                'ninas_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niñas de pre primaria a tercero primaria para la escuela.',
                'ninas_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niñas de pre primaria a tercero primaria debe ser un dato numerico.',
                'ninos_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niños de pre primaria a tercero primaria para la escuela.',
                'ninos_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niños de pre primaria a tercero primaria debe ser un dato numerico.',
                'total_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad total de estudiantes de pre primaria a tercero primaria para la escuela.',
                'total_pre_primaria_a_tercero_primaria.integer' => 'La cantidad total de estudiantes de pre primaria a tercero primaria debe ser un dato numerico.',
                'ninas_cuarto_a_sexto.required' => 'Se requiere la cantidad de niñas de cuarto a sexto primaria para la escuela.',
                'ninas_cuarto_a_sexto.integer' => 'La cantidad de niñas de cuarto a sexto primaria debe ser un dato numerico.',
                'ninios_cuarto_sexto.required' => 'Se requiere la cantidad de niños de cuarto a sexto primaria para la escuela.',
                'ninios_cuarto_sexto.integer' => 'La cantidad de niños de cuarto a sexto primaria debe ser un dato numerico.',
                'total_cuarto_a_sexto.required' => 'Se requiere la cantidad total de estudiantes de cuarto a sexto primaria para la escuela.',
                'total_cuarto_a_sexto.integer' => 'La cantidad total de estudiantes de cuarto a sexto primaria debe ser un dato numerico.',
                'total_de_estudiantes.required' => 'Se requiere la cantidad total de estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
                'total_de_estudiantes.integer' => 'La cantidad total de estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
                'total_de_raciones_de_estudiantes.required' => 'Se requiere la cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
                'total_de_raciones_de_estudiantes.integer' => 'La cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
                'total_docentes.required' => 'Se requiere la cantidad total de docentes de la escuela.',
                'total_docentes.integer' => 'La cantidad total de docentes debe ser un dato numerico.',
                'total_voluntarios.required' => 'Se requiere la cantidad total de voluntarios de la escuela.',
                'total_voluntarios.integer' => 'La cantidad total de voluntarios debe ser un dato numerico.',
                'total_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de docentes y voluntarios de la escuela.',
                'total_de_docentes_y_voluntarios.integer' => 'La cantidad total de docentes y voluntarios debe ser un dato numerico.',
                'total_de_raciones_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de raciones de docentes y voluntarios de la escuela.',
                'total_de_raciones_de_docentes_y_voluntarios.integer' => 'La cantidad total de raciones de docentes y voluntarios debe ser un dato numerico.',
                'total_de_personas.required' => 'Se requiere la cantidad total de personas de la escuela.',
                'total_de_personas.integer' => 'La cantidad total de personas debe ser un dato numerico.',
                'total_de_raciones.required' => 'Se requiere la cantidad total de raciones de la escuela.',
                'total_de_raciones.integer' => 'La cantidad total de raciones debe ser un dato numerico.',
                'tipo_de_actividad_alimentos.required' => 'Se requiere el tipo de actividad de alimentos de la racion asignada a la escuela.',
                'tipo_de_actividad_alimentos.string' => 'El tipo de actividad de alimentos de la racion asignada debe ser en relacion al tipo de actividad de la racion previamente registrada.',
                'numero_de_entrega.required' => 'Se requiere el numero de entrega de la escuela.',
                'numero_de_entrega.integer' => 'El numero de entrega debe ser un dato numerico.',
                'tipo.required' => 'Se requiere el tipo de entrega de la escuela.',
                'tipo.integer' => 'El tipo de entrega debe ser un dato de texto, numerico o alfanumerico.'
            ];
    
            $validator = Validator::make($csd, $reglas, $mensajes);

            if ($validator->fails()) :
                $s = Solicitud::findOrFail($idSolicitud);
                $s->observaciones = "Solicitud eliminada por mal ingreso de datos o formato de los mismos, en el archivo excel";
                $s->nombre_archivo = $archivo->getClientOriginalName();
                $s->save();
                $s = Solicitud::findOrFail($idSolicitud);
                $s->delete();
                return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
                

            else:
                

                $escuela = Escuela::where('codigo', $csd['codigo_de_la_escuela'])->first();
                $racion = Racion::where('tipo_alimentos', $csd['tipo_de_actividad_alimentos'])->where('id_institucion',  Auth::user()->id_institucion)->get()->first();

                $sd = new SolicitudDetalles;
                $sd->id_solicitud = $idSolicitud;
                $fecha= intval($csd['fecha_de_solicitud']);
                $sd->fecha = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha));
                $sd->id_escuela = $escuela->id;
                $sd->mes_de_solicitud = $csd['mes_de_solicitud'];
                $sd->dias_de_solicitud = $csd['dias_de_solicitud']; 
                $sd->ninas_pre_primaria_a_tercero_primaria = $csd['ninas_pre_primaria_a_tercero_primaria']; 
                $sd->ninos_pre_primaria_a_tercero_primaria = $csd['ninos_pre_primaria_a_tercero_primaria'];
                $sd->total_pre_primaria_a_tercero_primaria = $csd['total_pre_primaria_a_tercero_primaria'];
                $sd->ninas_cuarto_a_sexto = $csd['ninas_cuarto_a_sexto'];
                $sd->ninos_cuarto_a_sexto = $csd['ninios_cuarto_sexto'];
                $sd->total_cuarto_a_sexto = $csd['total_cuarto_a_sexto'];
                $sd->total_de_estudiantes = $csd['total_de_estudiantes'];
                $sd->total_de_raciones_de_estudiantes = $csd['total_de_raciones_de_estudiantes']; 
                $sd->total_docentes = $csd['total_docentes'];
                $sd->total_voluntarios = $csd['total_voluntarios'];
                $sd->total_de_docentes_y_voluntarios = $csd['total_de_docentes_y_voluntarios']; 
                $sd->total_de_raciones_de_docentes_y_voluntarios = $csd['total_de_raciones_de_docentes_y_voluntarios'];
                $sd->total_de_personas = $csd['total_de_personas'];
                $sd->total_de_raciones = $csd['total_de_raciones'];
                $sd->tipo_de_actividad_alimentos = $racion->id;
                $sd->numero_de_entrega = $csd['numero_de_entrega'];
                $sd->tipo = $csd['tipo'];
                $sd->save();
            endif;
        endforeach;

        Solicitud::where('id',$idSolicitud)->update(['nombre_archivo'=>$archivo->getClientOriginalName()]);

        $b = new Bitacora;
        $b->accion = 'Registro de solicitud de raciones con ID '.$idSolicitud;
        $b->id_usuario = Auth::id();
        $b->save();

        return redirect('/admin/solicitudes_despachos')->with('messages', '¡Solicitud creada y guardada con exito!.')
        ->with('typealert', 'success');

    }

    public function getSolicitudMostrar($id){
        $solicitud = Solicitud::with(['entrega', 'usuario','detalles'])->where('id', $id)->first();
        $total_estudiantes = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_estudiantes');
        $total_raciones_estudiantes = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_raciones_de_estudiantes');
        $total_docentes_voluntarios = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_docentes_y_voluntarios');
        $total_raciones_docentes_voluntarios = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_raciones_de_docentes_y_voluntarios');
        $total_personas = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_personas');
        $total_raciones = SolicitudDetalles::where('id_solicitud', $id)->sum('total_de_raciones');

        $datos = [
            'solicitud' => $solicitud,
            'total_estudiantes' => $total_estudiantes,
            'total_raciones_estudiantes' => $total_raciones_estudiantes,
            'total_docentes_voluntarios' => $total_docentes_voluntarios,
            'total_raciones_docentes_voluntarios' => $total_raciones_docentes_voluntarios,
            'total_personas' => $total_personas,
            'total_raciones' => $total_raciones
        ];

        return view('admin.solicitudes.mostrar',$datos);
        
    }

    public function getSolicitudDetallesRegistrar($id){
        $detalles = new SolicitudDetalles;
        $escuelas = Escuela::pluck('nombre','id');
        $solicitudes = Solicitud::with(['entrega', 'usuario'])->get();
        $consulta = Solicitud::where('id',$id)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->pluck('tipo_alimentos', 'id');
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->pluck('tipo_kits', 'id');
        endif;
        
        $idSolicitud = $id;
        $registrar = 1;

        $datos = [
            'detalles' => $detalles,
            'escuelas' => $escuelas,
            'raciones' => $raciones,
            'idSolicitud' => $idSolicitud,
            'registrar' => $registrar
        ];

        return view('admin.solicitudes.detalles.registrar',$datos);
    }

    public function postSolicitudDetallesRegistrar(Request $request){
        $reglas = [
            'fecha' => 'required',
            'mes_de_solicitud' => 'required|string',
            'dias_de_solicitud' => 'required|integer',
            'ninas_pre_primaria_a_tercero_primaria' => 'required|integer',
            'ninos_pre_primaria_a_tercero_primaria' => 'required|integer',
            'total_pre_primaria_a_tercero_primaria' => 'required|integer',
            'ninas_cuarto_a_sexto' => 'required|integer',
            'ninos_cuarto_a_sexto' => 'required|integer',
            'total_cuarto_a_sexto' => 'required|integer',
            'total_de_estudiantes' => 'required|integer',
            'total_de_raciones_de_estudiantes' => 'required|integer',
            'total_docentes' => 'required|integer',
            'total_voluntarios' => 'required|integer',
            'total_de_docentes_y_voluntarios' => 'required|integer',
            'total_de_raciones_de_docentes_y_voluntarios' => 'required|integer',
            'total_de_personas' => 'required|integer',
            'total_de_raciones' => 'required|integer',
            'tipo_de_actividad_alimentos' => 'required|string',
            'numero_de_entrega' => 'required|integer',
            'tipo' => 'required|string',
        ];
        $mensajes = [
            'fecha.required' => 'Se requiere una fecha de solicitud para la escuela.',
            'mes_de_solicitud.required' => 'Se requiere un mes de solicitud para la escuela.',
            'mes_de_solicitud.string' => 'El mes debe ser un dato texto.',
            'dias_de_solicitud.required' => 'Se requiere los dias a cubrir en la solicitud para la escuela.',
            'dias_de_solicitud.integer' => 'El dia debe ser un dato numerico.',
            'ninas_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niñas de pre primaria a tercero primaria para la escuela.',
            'ninas_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niñas de pre primaria a tercero primaria debe ser un dato numerico.',
            'ninos_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niños de pre primaria a tercero primaria para la escuela.',
            'ninos_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niños de pre primaria a tercero primaria debe ser un dato numerico.',
            'total_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad total de estudiantes de pre primaria a tercero primaria para la escuela.',
            'total_pre_primaria_a_tercero_primaria.integer' => 'La cantidad total de estudiantes de pre primaria a tercero primaria debe ser un dato numerico.',
            'ninas_cuarto_a_sexto.required' => 'Se requiere la cantidad de niñas de cuarto a sexto primaria para la escuela.',
            'ninas_cuarto_a_sexto.integer' => 'La cantidad de niñas de cuarto a sexto primaria debe ser un dato numerico.',
            'ninos_cuarto_a_sexto.required' => 'Se requiere la cantidad de niños de cuarto a sexto primaria para la escuela.',
            'ninos_cuarto_a_sexto.integer' => 'La cantidad de niños de cuarto a sexto primaria debe ser un dato numerico.',
            'total_cuarto_a_sexto.required' => 'Se requiere la cantidad total de estudiantes de cuarto a sexto primaria para la escuela.',
            'total_cuarto_a_sexto.integer' => 'La cantidad total de estudiantes de cuarto a sexto primaria debe ser un dato numerico.',
            'total_de_estudiantes.required' => 'Se requiere la cantidad total de estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
            'total_de_estudiantes.integer' => 'La cantidad total de estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
            'total_de_raciones_de_estudiantes.required' => 'Se requiere la cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
            'total_de_raciones_de_estudiantes.integer' => 'La cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
            'total_docentes.required' => 'Se requiere la cantidad total de docentes de la escuela.',
            'total_docentes.integer' => 'La cantidad total de docentes debe ser un dato numerico.',
            'total_voluntarios.required' => 'Se requiere la cantidad total de voluntarios de la escuela.',
            'total_voluntarios.integer' => 'La cantidad total de voluntarios debe ser un dato numerico.',
            'total_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de docentes y voluntarios de la escuela.',
            'total_de_docentes_y_voluntarios.integer' => 'La cantidad total de docentes y voluntarios debe ser un dato numerico.',
            'total_de_raciones_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de raciones de docentes y voluntarios de la escuela.',
            'total_de_raciones_de_docentes_y_voluntarios.integer' => 'La cantidad total de raciones de docentes y voluntarios debe ser un dato numerico.',
            'total_de_personas.required' => 'Se requiere la cantidad total de personas de la escuela.',
            'total_de_personas.integer' => 'La cantidad total de personas debe ser un dato numerico.',
            'total_de_raciones.required' => 'Se requiere la cantidad total de raciones de la escuela.',
            'total_de_raciones.integer' => 'La cantidad total de raciones debe ser un dato numerico.',
            'tipo_de_actividad_alimentos.required' => 'Se requiere el tipo de actividad de alimentos de la racion asignada a la escuela.',
            'tipo_de_actividad_alimentos.string' => 'El tipo de actividad de alimentos de la racion asignada debe ser en relacion al tipo de actividad de la racion previamente registrada.',
            'numero_de_entrega.required' => 'Se requiere el numero de entrega de la escuela.',
            'numero_de_entrega.integer' => 'El numero de entrega debe ser un dato numerico.',
            'tipo.required' => 'Se requiere el tipo de entrega de la escuela.',
            'tipo.integer' => 'El tipo de entrega debe ser un dato de texto, numerico o alfanumerico.'
        ];

        $validator = Validator::make($request->all(), $reglas, $mensajes);
    	if($validator->fails()):
    		return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
        else: 
            $detalle = new SolicitudDetalles;
            $detalle->id_solicitud = $request->input('id_solicitud');
            $detalle->fecha = $request->input('fecha');
            $detalle->id_escuela = $request->input('id_escuela');
            $detalle->mes_de_solicitud = $request->input('mes_de_solicitud');
            $detalle->dias_de_solicitud = $request->input('dias_de_solicitud'); 
            $detalle->ninas_pre_primaria_a_tercero_primaria = $request->input('ninas_pre_primaria_a_tercero_primaria'); 
            $detalle->ninos_pre_primaria_a_tercero_primaria = $request->input('ninos_pre_primaria_a_tercero_primaria');
            $detalle->total_pre_primaria_a_tercero_primaria = $request->input('total_pre_primaria_a_tercero_primaria');
            $detalle->ninas_cuarto_a_sexto = $request->input('ninas_cuarto_a_sexto');
            $detalle->ninos_cuarto_a_sexto = $request->input('ninos_cuarto_a_sexto');
            $detalle->total_cuarto_a_sexto = $request->input('total_cuarto_a_sexto');
            $detalle->total_de_estudiantes = $request->input('total_de_estudiantes');
            $detalle->total_de_raciones_de_estudiantes = $request->input('total_de_raciones_de_estudiantes'); 
            $detalle->total_docentes = $request->input('total_docentes');
            $detalle->total_voluntarios = $request->input('total_voluntarios');
            $detalle->total_de_docentes_y_voluntarios = $request->input('total_de_docentes_y_voluntarios'); 
            $detalle->total_de_raciones_de_docentes_y_voluntarios = $request->input('total_de_raciones_de_docentes_y_voluntarios');
            $detalle->total_de_personas = $request->input('total_de_personas');
            $detalle->total_de_raciones = $request->input('total_de_raciones');
            $detalle->tipo_de_actividad_alimentos = $request->input('tipo_de_actividad_alimentos');
            $detalle->numero_de_entrega = $request->input('numero_de_entrega');
            $detalle->tipo = $request->input('tipo');
            $id = $detalle->id;
            $idSolicitud = $detalle->id_solicitud;

            if($detalle->save()):
                $b = new Bitacora;
                $b->accion = 'Registro de información de la escuela de solicitud con registro (ID): '.$id;
                $b->id_usuario = Auth::id();
                $b->save();

                return redirect('/admin/solicitud_despacho/'.$idSolicitud.'/mostrar')->with('messages', '¡Información registrar y guardada con exito!.')
                ->with('typealert', 'info');
    		endif;
        endif;
    }

    public function getSolicitudDetallesEditar($id){
        $detalles = SolicitudDetalles::findOrFail($id);
        $escuelas = Escuela::pluck('nombre','id');
        //$raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->where('deleted_at',null)->pluck('nombre', 'id');
    

        $consulta = Solicitud::where('id',$detalles->id_solicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->where('deleted_at',null)->pluck('nombre', 'id');
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->where('deleted_at',null)->pluck('nombre', 'id');
        endif;

        //return $raciones.'-'.$detalles->id_solicitud.'-'.$tipo_insumo;
        $registrar = 0;

        $datos = [
            'detalles' => $detalles,
            'escuelas' => $escuelas,
            'raciones' => $raciones,
            'registrar' => $registrar
        ];

        return view('admin.solicitudes.detalles.editar',$datos);
    }   

    public function postSolicitudDetallesEditar(Request $request, $id){
        $reglas = [
            'fecha' => 'required',
            'mes_de_solicitud' => 'required|string',
            'dias_de_solicitud' => 'required|integer',
            'ninas_pre_primaria_a_tercero_primaria' => 'required|integer',
            'ninos_pre_primaria_a_tercero_primaria' => 'required|integer',
            'total_pre_primaria_a_tercero_primaria' => 'required|integer',
            'ninas_cuarto_a_sexto' => 'required|integer',
            'ninos_cuarto_a_sexto' => 'required|integer',
            'total_cuarto_a_sexto' => 'required|integer',
            'total_de_estudiantes' => 'required|integer',
            'total_de_raciones_de_estudiantes' => 'required|integer',
            'total_docentes' => 'required|integer',
            'total_voluntarios' => 'required|integer',
            'total_de_docentes_y_voluntarios' => 'required|integer',
            'total_de_raciones_de_docentes_y_voluntarios' => 'required|integer',
            'total_de_personas' => 'required|integer',
            'total_de_raciones' => 'required|integer',
            'tipo_de_actividad_alimentos' => 'required|string',
            'numero_de_entrega' => 'required|integer',
            'tipo' => 'required|string',
        ];
        $mensajes = [
            'fecha.required' => 'Se requiere una fecha de solicitud para la escuela.', 
            'mes_de_solicitud.required' => 'Se requiere un mes de solicitud para la escuela.',
            'mes_de_solicitud.string' => 'El mes debe ser un dato texto.',
            'dias_de_solicitud.required' => 'Se requiere los dias a cubrir en la solicitud para la escuela.', 
            'dias_de_solicitud.integer' => 'El dia debe ser un dato numerico.',
            'ninas_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niñas de pre primaria a tercero primaria para la escuela.',
            'ninas_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niñas de pre primaria a tercero primaria debe ser un dato numerico.',
            'ninos_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad de niños de pre primaria a tercero primaria para la escuela.',
            'ninos_pre_primaria_a_tercero_primaria.integer' => 'La cantidad de niños de pre primaria a tercero primaria debe ser un dato numerico.',
            'total_pre_primaria_a_tercero_primaria.required' => 'Se requiere la cantidad total de estudiantes de pre primaria a tercero primaria para la escuela.',
            'total_pre_primaria_a_tercero_primaria.integer' => 'La cantidad total de estudiantes de pre primaria a tercero primaria debe ser un dato numerico.',
            'ninas_cuarto_a_sexto.required' => 'Se requiere la cantidad de niñas de cuarto a sexto primaria para la escuela.',
            'ninas_cuarto_a_sexto.integer' => 'La cantidad de niñas de cuarto a sexto primaria debe ser un dato numerico.',
            'ninos_cuarto_a_sexto.required' => 'Se requiere la cantidad de niños de cuarto a sexto primaria para la escuela.',
            'ninos_cuarto_a_sexto.integer' => 'La cantidad de niños de cuarto a sexto primaria debe ser un dato numerico.',
            'total_cuarto_a_sexto.required' => 'Se requiere la cantidad total de estudiantes de cuarto a sexto primaria para la escuela.',
            'total_cuarto_a_sexto.integer' => 'La cantidad total de estudiantes de cuarto a sexto primaria debe ser un dato numerico.',
            'total_de_estudiantes.required' => 'Se requiere la cantidad total de estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
            'total_de_estudiantes.integer' => 'La cantidad total de estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
            'total_de_raciones_de_estudiantes.required' => 'Se requiere la cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) de la escuela.',
            'total_de_raciones_de_estudiantes.integer' => 'La cantidad total de raciones estudiantes (desde pre primaria hasta sexto primaria) debe ser un dato numerico.',
            'total_docentes.required' => 'Se requiere la cantidad total de docentes de la escuela.',
            'total_docentes.integer' => 'La cantidad total de docentes debe ser un dato numerico.',
            'total_voluntarios.required' => 'Se requiere la cantidad total de voluntarios de la escuela.',
            'total_voluntarios.integer' => 'La cantidad total de voluntarios debe ser un dato numerico.',
            'total_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de docentes y voluntarios de la escuela.',
            'total_de_docentes_y_voluntarios.integer' => 'La cantidad total de docentes y voluntarios debe ser un dato numerico.',
            'total_de_raciones_de_docentes_y_voluntarios.required' => 'Se requiere la cantidad total de raciones de docentes y voluntarios de la escuela.',
            'total_de_raciones_de_docentes_y_voluntarios.integer' => 'La cantidad total de raciones de docentes y voluntarios debe ser un dato numerico.',
            'total_de_personas.required' => 'Se requiere la cantidad total de personas de la escuela.',
            'total_de_personas.integer' => 'La cantidad total de personas debe ser un dato numerico.',
            'total_de_raciones.required' => 'Se requiere la cantidad total de raciones de la escuela.',
            'total_de_raciones.integer' => 'La cantidad total de raciones debe ser un dato numerico.',
            'tipo_de_actividad_alimentos.required' => 'Se requiere el tipo de actividad de alimentos de la racion asignada a la escuela.',
            'tipo_de_actividad_alimentos.string' => 'El tipo de actividad de alimentos de la racion asignada debe ser en relacion al tipo de actividad de la racion previamente registrada.',
            'numero_de_entrega.required' => 'Se requiere el numero de entrega de la escuela.',
            'numero_de_entrega.integer' => 'El numero de entrega debe ser un dato numerico.',
            'tipo.required' => 'Se requiere el tipo de entrega de la escuela.',
            'tipo.integer' => 'El tipo de entrega debe ser un dato de texto, numerico o alfanumerico.'
        ];

        $validator = Validator::make($request->all(), $reglas, $mensajes);
    	if($validator->fails()):
    		return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
        else: 
            $detalle = SolicitudDetalles::findOrFail($id);
            $detalle->id_solicitud = $request->input('id_solicitud');
            $detalle->fecha = $request->input('fecha');
            $detalle->id_escuela = $request->input('id_escuela');
            $detalle->mes_de_solicitud = $request->input('mes_de_solicitud');
            $detalle->dias_de_solicitud = $request->input('dias_de_solicitud'); 
            $detalle->ninas_pre_primaria_a_tercero_primaria = $request->input('ninas_pre_primaria_a_tercero_primaria'); 
            $detalle->ninos_pre_primaria_a_tercero_primaria = $request->input('ninos_pre_primaria_a_tercero_primaria');
            $detalle->total_pre_primaria_a_tercero_primaria = $request->input('total_pre_primaria_a_tercero_primaria');
            $detalle->ninas_cuarto_a_sexto = $request->input('ninas_cuarto_a_sexto');
            $detalle->ninos_cuarto_a_sexto = $request->input('ninos_cuarto_a_sexto');
            $detalle->total_cuarto_a_sexto = $request->input('total_cuarto_a_sexto');
            $detalle->total_de_estudiantes = $request->input('total_de_estudiantes');
            $detalle->total_de_raciones_de_estudiantes = $request->input('total_de_raciones_de_estudiantes'); 
            $detalle->total_docentes = $request->input('total_docentes');
            $detalle->total_voluntarios = $request->input('total_voluntarios');
            $detalle->total_de_docentes_y_voluntarios = $request->input('total_de_docentes_y_voluntarios'); 
            $detalle->total_de_raciones_de_docentes_y_voluntarios = $request->input('total_de_raciones_de_docentes_y_voluntarios');
            $detalle->total_de_personas = $request->input('total_de_personas');
            $detalle->total_de_raciones = $request->input('total_de_raciones');
            $detalle->tipo_de_actividad_alimentos = $request->input('tipo_de_actividad_alimentos');
            $detalle->numero_de_entrega = $request->input('numero_de_entrega');
            $detalle->tipo = $request->input('tipo');
            $idSolicitud = $detalle->id_solicitud;

            if($detalle->save()):
                $b = new Bitacora;
                $b->accion = 'Edición de información de la escuela de solicitud con registro (ID): '.$id;
                $b->id_usuario = Auth::id();
                $b->save();

                return redirect('/admin/solicitud_despacho/'.$idSolicitud.'/mostrar')->with('messages', '¡Información actualizada y guardada con exito!.')
                ->with('typealert', 'info');
    		endif;
        endif;
    }

    public function getSolicitudEliminar($id){
        $solicitud = Solicitud::findOrFail($id);
        $detalles = SolicitudDetalles::where('id_solicitud',$id)->delete();



        if($solicitud->delete()):
            $b = new Bitacora;
            $b->accion = 'Eliminacion de solicitud registro (ID): '.$solicitud->id;
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Escuela eliminada con exito!.')
                    ->with('typealert', 'warning');
        endif;
    }

    public function getSolicitudDetallesEliminar($id){
        $detalle = SolicitudDetalles::findOrFail($id);

        if($detalle->delete()):
            $b = new Bitacora;
            $b->accion = 'Eliminacion de detalle de solicitud registro (ID): '.$detalle->id;
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Escuela eliminada con exito!.')
                    ->with('typealert', 'warning');
        endif;
    }

    public function getSolicitudRutas($id){
        $rutas_principales = Ruta::with('detalles')->orderBy('id_ubicacion', 'asc')->where('id_socio', Auth::user()->id_institucion)->get();

        $idSolicitud = $id;
        $datos = [
            'rutas_principales' => $rutas_principales,
            'idSolicitud' => $idSolicitud
        ];

        return view('admin.solicitudes.detalles.rutas',$datos);
    }

    public function getSolicitudEscuelas($id){
        $escuelas_principales = Escuela::where('id_socio', Auth::user()->id_institucion)->get();

        $idSolicitud = $id;
        $datos = [
            'escuelas_principales' => $escuelas_principales,
            'idSolicitud' => $idSolicitud
        ];

        return view('admin.solicitudes.boletas_despacho.escuelas',$datos);
    }

    public function getSolicitudEscuelaDespacho($id, $idEscuela){
        
        $despachos = BodegaEgreso::with(['detalles', 'escuela'])->where('id_solicitud_despacho', $id)->where('id_escuela_despacho', $idEscuela)->get();
        $escuelas_principales = Escuela::where('id_socio', Auth::user()->id_institucion)->get();
        $consulta = Solicitud::where('id',$id)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
        endif;
        //$kits = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
        $idSolicitud = $id;
            
        $datos = [
            'despachos' => $despachos,
            'raciones' => $raciones,
            //'kits' => $kits,
            'escuelas_principales' => $escuelas_principales,
            'idSolicitud' => $idSolicitud
        ];
          

        return view('admin.solicitudes.boletas_despacho.escuelas_despachos',$datos);
    }

    public function getSolicitudEscuelaDespachoPDF($idSolicitud, $idEscuela, $id){     
        
        $despachos = BodegaEgreso::with(['detalles', 'escuela'])->where('id', $id)->where('id_solicitud_despacho', $idSolicitud)->where('id_escuela_despacho', $idEscuela)->get();
        
        $consulta = Solicitud::where('id',$idSolicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
        endif;
        //$kits = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
        $datos = [
            'raciones' => $raciones,
            //'kits' => $kits,
            'despachos' => $despachos 
        ];

        $ancho = 612; //8 1/2 
        $largo = 396; //5 1/2 
        $customPaper = array(0,0,$largo,$ancho);
        $pdf = Pdf::loadView('admin.solicitudes.boletas_despacho.pdf', $datos);
     
        return $pdf->stream();
          
    }

    public function getSolicitudRutaDetalle($id, $idRuta){
        $rutas_principales = Ruta::with('detalles')->orderBy('id_ubicacion', 'asc')->where('id_socio', Auth::user()->id_institucion)->get();
        $ruta = Ruta::where('id', $idRuta)->first();
        $detalles_ruta_escuelas = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw("CONCAT(escuelas.nombre, ' (',escuelas.codigo,')') as escuela"),
                    DB::raw('rutas_escuelas.orden_llegada as orden_llegada'),
                    DB::raw('SUM(solicitud_detalles.total_de_raciones) as total_raciones')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join('rutas_escuelas', 'rutas_escuelas.id_escuela', 'escuelas.id' )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $id)
                ->where('rutas_escuelas.id_ruta', $idRuta)
                ->groupBy('solicitud_detalles.id_escuela', 'escuelas.id','escuelas.codigo',  'escuelas.nombre', 'rutas_escuelas.orden_llegada')
                ->orderBy('rutas_escuelas.orden_llegada', 'asc')
                ->get();
        
        $idSolicitud = $id;
        
        $escuelas = DB::table('escuelas as e')
                    ->select('e.id', 'e.codigo', 'e.nombre')
                    ->join('rutas_escuelas', 'rutas_escuelas.id_escuela','e.id')
                    ->where('rutas_escuelas.id_ruta', $idRuta)
                    ->get();
                

        $nombre_ruta = $ruta->ubicacion->nomenclatura.'0'.$ruta->correlativo;

        $ruta_despacho = RutaSolicitud::with('detalles')
            ->where('id_ruta_base', $idRuta)
            ->where('nombre',$nombre_ruta)
            ->where('deleted_at', null)
            ->first();        
        $sub_rutas = RutaSolicitud::with('detalles')
            ->where('id_ruta_base', $idRuta)
            ->whereNot('nombre',$nombre_ruta)
            ->where('deleted_at', null)
            ->get();

        $detalle_escuelas = DB::table('solicitud_detalles')
            ->select(
                DB::raw('escuelas.id as escuela_id'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                DB::raw('( ( SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) + SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) + SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios)  ) ) as peso')
            )
            ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
            ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
            })
            ->where('solicitud_detalles.id_solicitud', $id)            
            ->where('solicitud_detalles.deleted_at', null)
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [4])
            ->groupBy('escuelas.id', 'solicitud_detalles.tipo_de_actividad_alimentos','alimentos_racion.peso')
            ->get();
            //return $detalle_escuelas;
        $tipo_ruta;
        if(isset($ruta_despacho) ): 
            $tipo_ruta = 0;
        elseif(count($sub_rutas) > 0):
            $tipo_ruta = 1;
        else:
            $tipo_ruta = 2;    
        endif;


        if(count($detalles_ruta_escuelas) > 0):
            $idEscuelas;
            foreach($detalles_ruta_escuelas as $det):
                $idEscuelas[] = $det->escuela_id;
            endforeach;

            $consulta = Solicitud::where('id',$id)->get();
            foreach($consulta as $c):
                $tipo_insumo = $c->tipo_insumos;
            endforeach;
            
            if($tipo_insumo == "1"):
                $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();

            //return $raciones; 
            
                foreach($raciones as $r):
                    if($r->nombre =="Escolar"):
                        $id_escolar_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar2"):
                        $id_escolar2_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar expansión"):
                        $id_escolar_expansion_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar2 expansión"):
                        $id_escolar2_expansion_racion = $r->id;
                    endif;


                    if($r->nombre =="Líderes"):
                        $id_lideres_racion = $r->id;
                    endif;

                    if($r->nombre =="Líderes expansión"):
                        $id_lideres_expansion_racion = $r->id;
                    endif;

                    if($r->nombre =="Voluntarios"):
                        $id_do_vo_racion = $r->id;
                    endif;     
                    
                    if($r->nombre =="Voluntarios expansión"):
                        $id_do_vo_expansion_racion = $r->id;
                    endif; 
                endforeach;
            else:
                $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();

                foreach($raciones as $r):
                    if($r->nombre =="Escolar"):
                        $id_escolar_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar2"):
                        $id_escolar2_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar expansión"):
                        $id_escolar_expansion_racion = $r->id;
                    endif;

                    if($r->nombre =="Escolar2 expansión"):
                        $id_escolar2_expansion_racion = $r->id;
                    endif;


                    if($r->nombre =="Líderes"):
                        $id_lideres_racion = $r->id;
                    endif;

                    if($r->nombre =="Líderes expansión"):
                        $id_lideres_expansion_racion = $r->id;
                    endif;

                    if($r->nombre =="Voluntarios"):
                        $id_do_vo_racion = $r->id;
                    endif;     
                    
                    if($r->nombre =="Voluntarios expansión"):
                        $id_do_vo_expansion_racion = $r->id;
                    endif; 
                endforeach;
            endif;

            

            $det_escuelas_preprimaria_enc =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('raciones.id as idracion'),
                DB::raw('raciones.nombre as racion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $id)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
            ->get();


            $det_escuelas_preprimaria =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_ninos'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id', 'solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();
            /*foreach($det_escuelas_preprimaria as $det_pre):

                $idRacionPre = $det_pre->racion;
            endforeach;*/
                //return $det_escuelas_preprimaria;

            if(isset($id_escolar2_racion) ):
                //return $id_escolar2_racion;
                $det_escuelas_primaria_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $id)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();

                $det_escuelas_primaria = DB::table('solicitud_detalles')
                    ->select(
                        DB::raw('escuelas.id as escuela_id'),
                        DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                        DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_ninos'),
                        DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                        DB::raw('alimentos_racion.peso as peso_racion')
                    )
                    ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                    ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j)  use($id_escolar2_racion){
                        $j->where("alimentos_racion.id_racion","=",$id_escolar2_racion);
                    })
                    ->where('solicitud_detalles.id_solicitud', $id)
                    ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                    ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                    ->where('solicitud_detalles.deleted_at', null)
                    ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                    ->get();
            else:
                $det_escuelas_primaria = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_ninos'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();
            endif;

            if(isset($id_escolar_expasion_racion) ):
                //return $id_escolar2_racion;
                $det_escuelas_preprimaria_ex = DB::table('solicitud_detalles')
                    ->select(
                        DB::raw('escuelas.id as escuela_id'),
                        DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                        DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_ninos'),
                        DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                        DB::raw('alimentos_racion.peso as peso_racion')
                    )
                    ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                    ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j)  use($id_escolar_expansion_racion){
                        $j->where("alimentos_racion.id_racion","=",$id_escolar_expansion_racion);
                    })
                    ->where('solicitud_detalles.id_solicitud', $id)
                    ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                    ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                    ->where('solicitud_detalles.deleted_at', null)
                    ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                    ->get();
            else:
                $det_escuelas_preprimaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_ninos'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();
            endif;

            if(isset($id_escolar2_expasion_racion) ):
                //return $id_escolar2_racion;
                $det_escuelas_primaria_ex = DB::table('solicitud_detalles')
                    ->select(
                        DB::raw('escuelas.id as escuela_id'),
                        DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                        DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_ninos'),
                        DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                        DB::raw('alimentos_racion.peso as peso_racion')
                    )
                    ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                    ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j)  use($id_escolar2_expansion_racion){
                        $j->where("alimentos_racion.id_racion","=",$id_escolar2_expansion_racion);
                    })
                    ->where('solicitud_detalles.id_solicitud', $id)
                    ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                    ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                    ->where('solicitud_detalles.deleted_at', null)
                    ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                    ->get();
            else:
                $det_escuelas_primaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_ninos'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();
            endif;
                //return $det_escuelas_preprimaria;

            $det_escuelas_l_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $id)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get(); 

            $det_escuelas_l = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos',$id_lideres_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();

                if(isset($id_lideres_expansion_racion) ):
                    //return $id_escolar2_racion;
                    $det_escuelas_l_ex = DB::table('solicitud_detalles')
                        ->select(
                            DB::raw('escuelas.id as escuela_id'),
                            DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                            DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                            DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                            DB::raw('alimentos_racion.peso as peso_racion')
                        )
                        ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                        ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j) use($id_lideres_expansion_racion){
                            $j->where("alimentos_racion.id_racion","=",$id_lideres_expansion_racion);
                        })
                        ->where('solicitud_detalles.id_solicitud', $id)
                        ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                        ->where('solicitud_detalles.tipo_de_actividad_alimentos',$id_lideres_racion)                
                        ->where('solicitud_detalles.deleted_at', null)
                        ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                        ->get();
                else:
                    $det_escuelas_l_ex = DB::table('solicitud_detalles')
                        ->select(
                            DB::raw('escuelas.id as escuela_id'),
                            DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                            DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                            DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                            DB::raw('alimentos_racion.peso as peso_racion')
                        )
                        ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                        ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                            $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                        })
                        ->where('solicitud_detalles.id_solicitud', $id)
                        ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                        ->where('solicitud_detalles.tipo_de_actividad_alimentos',$id_lideres_racion)                
                        ->where('solicitud_detalles.deleted_at', null)
                        ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                        ->get();
                endif;
                $det_escuelas_v_d_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $id)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();        

            $det_escuelas_v_d = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('escuelas.id as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    DB::raw('alimentos_racion.peso as peso_racion')
                )
                ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->where('solicitud_detalles.id_solicitud', $id)
                ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                ->get();

                if(isset($id_do_vo_expansion_racion) ):
                    //return $id_escolar2_racion;
                    $det_escuelas_v_d_ex = DB::table('solicitud_detalles')
                        ->select(
                            DB::raw('escuelas.id as escuela_id'),
                            DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                            DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                            DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                            DB::raw('alimentos_racion.peso as peso_racion')
                        )
                        ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                        ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j)  use($id_do_vo_expansion_racion){
                            $j->where("alimentos_racion.id_racion","=",$id_do_vo_expansion_racion);
                        })
                        ->where('solicitud_detalles.id_solicitud', $id)
                        ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                        ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)
                        ->where('solicitud_detalles.deleted_at', null)
                        ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                        ->get();

                else:
                    $det_escuelas_v_d_ex = DB::table('solicitud_detalles')
                        ->select(
                            DB::raw('escuelas.id as escuela_id'),
                            DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                            DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_personas'),
                            DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                            DB::raw('alimentos_racion.peso as peso_racion')
                        )
                        ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
                        ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                            $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                        })
                        ->where('solicitud_detalles.id_solicitud', $id)
                        ->whereIn('solicitud_detalles.id_escuela', $idEscuelas)
                        ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)
                        ->where('solicitud_detalles.deleted_at', null)
                        ->groupBy('escuelas.id','solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.peso')
                        ->get();
                endif;
            
            
            $datos = [
                'rutas_principales' => $rutas_principales,
                'ruta' => $ruta,
                'ruta_despacho' => $ruta_despacho,
                'sub_rutas' => $sub_rutas,
                'tipo_ruta' => $tipo_ruta,
                'escuelas' => $escuelas,
                'idSolicitud' => $idSolicitud,
                'detalles_ruta_escuelas' => $detalles_ruta_escuelas,
                'det_escuelas_preprimaria_enc' =>$det_escuelas_preprimaria_enc,
                'det_escuelas_preprimaria' => $det_escuelas_preprimaria,
                'det_escuelas_primaria_enc' =>$det_escuelas_primaria_enc,
                'det_escuelas_primaria' => $det_escuelas_primaria,
                'det_escuelas_preprimaria_ex' => $det_escuelas_preprimaria_ex,
                'det_escuelas_primaria_ex' => $det_escuelas_primaria_ex,
                'det_escuelas_v_d_enc' => $det_escuelas_v_d_enc,
                'det_escuelas_v_d' => $det_escuelas_v_d,
                'det_escuelas_v_d_ex' => $det_escuelas_v_d_ex,
                'det_escuelas_l_enc' =>$det_escuelas_l_enc,
                'det_escuelas_l' => $det_escuelas_l,
                'det_escuelas_l_ex' => $det_escuelas_l_ex,
                'detalle_escuelas' => $detalle_escuelas
            ];

        else:
            $datos = [
                'rutas_principales' => $rutas_principales,
                'ruta' => $ruta,
                'ruta_despacho' => $ruta_despacho,
                'sub_rutas' => $sub_rutas,
                'tipo_ruta' => $tipo_ruta,
                'escuelas' => $escuelas,
                'idSolicitud' => $idSolicitud,
                'detalles_ruta_escuelas' => $detalles_ruta_escuelas,
                'detalle_escuelas' => $detalle_escuelas
            ];

        endif;        

        return view('admin.solicitudes.detalles.rutas_desgloce',$datos);
    }

    public function postSolicitudRutaConfirmar(Request $request){
        $idSolicitud = $request->input('id_solicitud');
        $ruta_base = $request->input('ruta_base');
        $nombre_ruta = e($request->input('nombre_ruta_solicitud'));
        $escuelas = RutaEscuela::select('id_escuela', 'orden_llegada')->where('id_ruta', $ruta_base)->get();

        DB::beginTransaction();
            $ruta_solicitud = new RutaSolicitud;
            $ruta_solicitud->id_solicitud_despacho = $idSolicitud;
            $ruta_solicitud->id_ruta_base = $ruta_base;
            $ruta_solicitud->nombre = $nombre_ruta;
            $ruta_solicitud->save();

            foreach($escuelas as $escuela):
                $detalle = new RutaSolicitudDetalles;
                $detalle->id_ruta_despacho = $ruta_solicitud->id;
                $detalle->id_escuela= $escuela->id_escuela;
                $detalle->orden_llegada= $escuela->orden_llegada;
                $detalle->save();
            endforeach;

        DB::commit();
 
        if($ruta_solicitud->save()):
            $b = new Bitacora;
            $b->accion = 'Confirmación de ruta '.$nombre_ruta.' sin fraccionar';
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Confirmación de ruta sin fraccionar!.')
                ->with('typealert', 'success');
        endif;
    }

    public function getSolicitudRutaConfirmadaEliminar($id){
        $ruta = RutaSolicitud::findOrFail($id);
        $detalles = RutaSolicitudDetalles::where('id_ruta_despacho',$id)->delete();

        $nombre = $ruta->nombre;
        $solicitud = $ruta->id_solicitud;


        if($ruta->delete()):
            $b = new Bitacora;
            $b->accion = 'Eliminacion de ruta '.$nombre.' de la solicitud (ID): '.$solicitud;
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Escuela eliminada con exito!.')
                    ->with('typealert', 'warning');
        endif;

    }

    public function postSolicitudCrearSubRuta(Request $request){       
        $abecedario = range('A','Z');
        $ruta_base = $request->input('ruta_base');
        $ruta_nombre_base = e($request->input('nombre_ruta_solicitud'));
        $conteo_ruta = RutaSolicitud::where('id_ruta_base', $ruta_base)->where('deleted_at', null)->count();
        $idSolicitud = $request->input('id_solicitud');

        $ruta_solicitud = new RutaSolicitud;
        $ruta_solicitud->id_solicitud_despacho = $idSolicitud;
        $ruta_solicitud->id_ruta_base = $ruta_base;
        $ruta_solicitud->nombre = $ruta_nombre_base.$abecedario[$conteo_ruta];

        if($ruta_solicitud->save()):
            $b = new Bitacora;
            $b->accion = 'Creacion de sub-ruta con exito';
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Creacion de sub-ruta con exito!.')
                ->with('typealert', 'success');
        endif;
    }

    public function postSolicitudAsignarEscuelaSubRuta(Request $request){       

        $detalle = new RutaSolicitudDetalles;
        $detalle->id_ruta_despacho = $request->input('id_sub_ruta_despacho');
        $detalle->id_escuela= $request->input('id_escuela');
        $detalle->orden_llegada= $request->input('orden_llegada');

        if($detalle->save()):
            $b = new Bitacora;
            $b->accion = 'Asignacion de escuela a sub ruta con exito';
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Asignacion de escuela a sub ruta con exito!.')
                ->with('typealert', 'success');
        endif;
    }

    public function getSolicitudEscuelaSubRutaEliminar($id){
        $escuela_sub_ruta = RutaSolicitudDetalles::findOrFail($id);


        if($escuela_sub_ruta->delete()):
            $b = new Bitacora;
            $b->accion = 'Eliminacion de escuela a sub ruta asignada';
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Escuela eliminada de sub ruta con exito!.')
                    ->with('typealert', 'warning');
        endif;

    }

    public function getSolicitudSubRutaEliminar($id){
        $ruta = RutaSolicitud::findOrFail($id);
        $detalles = RutaSolicitudDetalles::where('id_ruta_despacho',$id)->delete(); 

        $nombre = $ruta->nombre;
        $solicitud = $ruta->id_solicitud;


        if($ruta->delete()):
            $b = new Bitacora;
            $b->accion = 'Eliminacion de sub ruta '.$nombre.' de la solicitud (ID): '.$solicitud;
            $b->id_usuario = Auth::id();
            $b->save();

            return back()->with('messages', '¡Sub ruta eliminada con exito!.')
                    ->with('typealert', 'warning');
        endif;

    }

    public function getSolicitudRutasConfirmadas($id){
        $idSolicitud = $id;
        $rutas = RutaSolicitud::with('ruta_base')->where('id_solicitud_despacho',$id)->get();
        
        $consulta = Solicitud::where('id',$id)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::select('id')->where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $raciones = Kit::select('id')->where('id_institucion', Auth::user()->id_institucion)->get();
        endif;
        //$kits = Kit::select('id')->where('id_institucion', Auth::user()->id_institucion)->get();

        
        $detalle_escuelas = DB::table('solicitud_detalles')
            ->select(
                DB::raw('escuelas.id as escuela_id'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                DB::raw('( ( SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) + SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) + SUM(Distinct solicitud_detalles.dias_de_solicitud) * alimentos_racion.peso * SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios)  ) ) as peso')
            )
            ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
            ->join(DB::RAW("(SELECT id_racion, SUM(cantidad) as peso FROM alimentos_raciones GROUP BY id_racion) as alimentos_racion"), function($j){
                $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos"); 
            })
            ->where('solicitud_detalles.id_solicitud', $id) 
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', $raciones)
            ->groupBy('escuelas.id', 'solicitud_detalles.tipo_de_actividad_alimentos','alimentos_racion.peso')
            ->get();



        //return $detalle_escuelas;

        $datos = [
            'rutas' => $rutas,
            'idSolicitud' => $idSolicitud,
            'detalle_escuelas' => $detalle_escuelas
        ];


        return view('admin.solicitudes.rutas_confirmadas',$datos);

    }

    public function getSolicitudRutasConfirmadasTransporte($id){
        $ruta = RutaSolicitud::with('ruta_base')->where('id',$id)->first();

        $datos = [
            'ruta' => $ruta,
        ];


        return view('admin.solicitudes.rutas_confirmada_transporte',$datos);

    }

    public function postSolicitudRutasConfirmadasTransporte(Request $request){
        $reglas = [
    		
    	];
    	$mensajes = [
    	];

        $validator = Validator::make($request->all(), $reglas, $mensajes);
    	if($validator->fails()):
    		return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
        else: 
            $r = RutaSolicitud::findOrFail($request->input('idRuta'));
            $r->empresa_transporte = $request->input('empresa_transporte');
            $r->nombre_piloto = $request->input('nombre_piloto');
            $r->no_licencia = $request->input('no_licencia');
            $r->placa_vehiculo = $request->input('placa_vehiculo');
            $r->tipo_vehiculo = $request->input('tipo_vehiculo');
            $r->fecha_traslado = $request->input('fecha_traslado');
            $r->direccion_emision = $request->input('direccion_emision');


            if($r->save()):
                $b = new Bitacora;
                $b->accion = 'Se agrego informacion de transporte a la ruta'.$r->nombre;
                $b->id_usuario = Auth::id();
                $b->save();

                return back()->with('messages', '¡Se agrego inforamcion de transporte y se guardo con exito!.')
                    ->with('typealert', 'info');
    		endif;
        endif;

    }

    public function getSolicitudRutaConfirmadaPDF($idSolicitud, $idRuta){     
        
        $idSolicitud = $idSolicitud;
        $ruta = RutaSolicitud::with(['ruta_base', 'detalles'])->where('id',$idRuta)->first();
        
        $detalle_escuelas = DB::table('rutas_solicitudes_despachos as r')   
            ->select(
                'e.id as escuela_id',
                'e.codigo as escuela_codigo',
                'e.nombre as escuela_nombre',
                'be.id as egreso',
                'be.participantes as participantes',
                'be.no_documento as boleta',
                'ra.id as idracion',
                'ra.nombre as racion',

            )         
            ->join('rutas_solicitudes_despachos_detalles as rdet', 'rdet.id_ruta_despacho', 'r.id')
            ->join('escuelas as e', 'e.id', 'rdet.id_escuela')
            ->join('bodegas_egresos as be', 'be.id_escuela_despacho', 'rdet.id_escuela')
            ->join('raciones as ra', 'ra.id', 'be.tipo_racion')
            ->where('r.id', $idRuta)
            ->where('r.id_solicitud_despacho', $idSolicitud)       
            ->orderby('e.id', 'ASC')  
            ->get();

        $totales_alimentos = DB::table('rutas_solicitudes_despachos as r')   
            ->select(
                'bdet.id_insumo as insumo',
                DB::RAW('SUM(bdet.no_unidades) as total_insumo')
            )         
            ->join('rutas_solicitudes_despachos_detalles as rdet', 'rdet.id_ruta_despacho', 'r.id')
            ->join('escuelas as e', 'e.id', 'rdet.id_escuela')
            ->join('bodegas_egresos as be', 'be.id_escuela_despacho', 'rdet.id_escuela')
            ->join('bodegas_egresos_detalles as bdet', 'bdet.id_egreso', 'be.id')
            ->where('r.id', $idRuta)
            ->where('r.id_solicitud_despacho', $idSolicitud)       
            ->groupBy('bdet.id_insumo')  
            ->get();

        
        
        $detalles = $detalle_escuelas->map(function ($detalle_escuelas){
            $detalles_alimentos = DB::table('bodegas_egresos_detalles as det')   
            ->select(
                'det.id_insumo',DB::raw('SUM(det.no_unidades) as no_unidades')

            )      
            ->where('det.id_egreso', $detalle_escuelas->egreso)
            ->groupBy('det.id_insumo') 
            ->get();


            return collect([
                'escuela_id' => $detalle_escuelas->escuela_id,
                'idracion' => $detalle_escuelas->idracion,
                'detalles_alimentos' => $detalles_alimentos->map(function ($detalles_alimentos){
                    return [
                        'id_insumo' => $detalles_alimentos->id_insumo,
                        'no_unidades' => $detalles_alimentos->no_unidades,
                    ];
                }),
            ]);
        });

        //return $detalles;

        $deta[] = $detalles;


        $consulta = Solicitud::where('id',$idSolicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $alimentos = Bodega::with('pesos_alimento')->where('categoria' , 0)->where('tipo_bodega',1)->where('id_institucion', Auth::user()->id_institucion)->where('deleted_at',NULL)->orderBy('id', 'Asc')->get();
        else:
            $alimentos = Bodega::where('categoria' , 1)->where('tipo_bodega',1)->where('id_institucion', Auth::user()->id_institucion)->where('deleted_at',NULL)->orderBy('id', 'Asc')->get();
        endif;
        
        $solicitud = Solicitud::with(['entrega', 'usuario'])->where('id', $idSolicitud)->first();
        $encargado = Usuario::where('rol',4)->where('id_institucion', Auth::user()->id_institucion)->get();
        
        
        $datos = [
            'ruta' => $ruta,
            'idSolicitud' => $idSolicitud,
            'detalle_escuelas' => $detalle_escuelas,
            'detalles' => $detalles,
            'alimentos' => $alimentos,
            'totales_alimentos' => $totales_alimentos, 
            'solicitud' => $solicitud,
            'encargado' => $encargado
        ];

        $pdf = Pdf::loadView('admin.solicitudes.boleta_ruta_confirmada_pdf', $datos)->setPaper('letter');
     
        return $pdf->stream();


          
    }

    public function postActualizarOrdenLlegadaSubRutas(Request $request){
        $reglas = [

    	];
    	$mensajes = [

    	];

        $validator = Validator::make($request->all(), $reglas, $mensajes);
    	if($validator->fails()):
    		return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
        else: 
            

            $as = RutaSolicitudDetalles::findOrFail($request->input('id_asignacion'));
            $as->orden_llegada = e($request->input('orden'));

            if($as->save()):

                return back()->with('messages', '¡Actualizacion de orden de llegada con exito!.')
                    ->with('typealert', 'info');
    		endif;
        endif;
    }

    public function getEscuelasDespacho($id_solicitud){
        $escuelas = SolicitudDetalles::with('escuela')->select('id_escuela')->where('id_solicitud', $id_solicitud)->groupBy('id_escuela')->orderBy('id_escuela')->get();

        $datos = [
            'escuelas' => $escuelas
        ];

        return response()->json($datos);
    }

    public function getEscuelasPesosDespacho($solicitud, $escuela){
        $idRaciones;         
        
        $consulta = Solicitud::where('id',$solicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
        endif;

        foreach($raciones as $r):
            $idRaciones[] = $r->id;
        endforeach;

        $detalles_escuelas = DB::table('solicitud_detalles')
            ->select(
                DB::raw('escuelas.id as escuela_id'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                DB::raw('alimentos_racion.cantidad as cantidad_alimento'),
                DB::raw('alimentos_racion.id_alimento as alimento'),
                DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as preprimaria'),
                DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as primaria'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as ldv'),

            )
            ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
            ->join(DB::RAW("(SELECT id_racion, cantidad, id_alimento FROM alimentos_raciones GROUP BY id_racion, cantidad, id_alimento) as alimentos_racion"), function($j){
                $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
            })
            ->where('solicitud_detalles.id_solicitud', $solicitud)            
            ->where('solicitud_detalles.id_escuela', $escuela)  
            ->where('solicitud_detalles.deleted_at', null)
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [$idRaciones])
            ->groupBy('escuelas.id', 'solicitud_detalles.tipo_de_actividad_alimentos', 'alimentos_racion.cantidad','alimentos_racion.id_alimento')
            ->get();

        $datos = [
            'detalles_escuelas' => $detalles_escuelas
        ];
        return response()->json($datos);

    }

    public function getSolicitudABodegaPrimaria($solicitud){

        $consulta = Solicitud::where('id',$solicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        //return $consulta;
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Escolar"):
                    $id_escolar_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2"):
                    $id_escolar2_racion = $r->id;
                endif;

                if($r->nombre =="Escolar expansión"):
                    $id_escolar_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 expansión"):
                    $id_escolar2_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar SLLR"):
                    $id_escolar_sllr = $r->id;
                endif;

                if($r->nombre =="Líderes"):
                    $id_lideres_racion = $r->id;
                endif;

                if($r->nombre =="Líderes expansión"):
                    $id_lideres_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Voluntarios"):
                    $id_do_vo_racion = $r->id;
                endif;     
                
                if($r->nombre =="Voluntarios expansión"):
                    $id_do_vo_expansion_racion = $r->id;
                endif; 

                if($r->nombre =="Escolar Ordinario"):
                    $id_escolar_ordinario_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 Ordinario"):
                    $id_escolar2_ordinario_racion = $r->id;
                endif;

                if($r->nombre =="Líderes Ordinario"):
                    $id_lideres_ordinario_racion = $r->id;
                endif;

                if($r->nombre =="Voluntario Ordinario"):
                    $id_do_vo_ordinario_racion = $r->id;
                endif;
            endforeach;
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Escolar"):
                    $id_escolar_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2"):
                    $id_escolar2_racion = $r->id;
                endif;

                if($r->nombre =="Escolar expansión"):
                    $id_escolar_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 expansión"):
                    $id_escolar2_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Líderes"):
                    $id_lideres_racion = $r->id;
                endif;

                if($r->nombre =="Líderes expansión"):
                    $id_lideres_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Voluntarios"):
                    $id_do_vo_racion = $r->id;
                endif;     
                
                if($r->nombre =="Voluntarios expansión"):
                    $id_do_vo_expansion_racion = $r->id;
                endif; 
            endforeach;
        endif;

        //return $tipo_insumo;

        //return $id_escolar_racion;

        $escuelas = DB::table('solicitud_detalles')
            ->select(
                DB::raw('escuelas.id as escuela_id'),
                DB::raw('CONCAT(escuelas.codigo,  \' / \' , escuelas.nombre)  as escuela'),
            )
            ->join('escuelas', 'escuelas.id', 'solicitud_detalles.id_escuela')
            ->where('solicitud_detalles.id_solicitud', $solicitud)            
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('escuelas.id', 'escuelas.nombre', 'escuelas.codigo')
            ->get();

        $det_escuelas_preprimaria_enc =  DB::table('solicitud_detalles')
        ->select(
            DB::raw('solicitud_detalles.id_escuela as escuela_id'),
            DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
            DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
            DB::raw('raciones.id as idracion'),
            DB::raw('raciones.nombre as racion'),
        )
        ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
        ->where('solicitud_detalles.id_solicitud', $solicitud)  
        ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
        ->where('solicitud_detalles.deleted_at', null)
        ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
        ->get();
        //return $det_escuelas_preprimaria_enc;
        $det_escuelas_preprimaria =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                DB::raw('bodegas.id as alimento_id'),
                DB::raw('bodegas.nombre as alimento'),
                DB::raw('alimentos_racion.cantidad as alimento_peso'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
            )
            ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_racion){
                $j->where("alimentos_racion.id_racion","=",$id_escolar_racion);
            })
            ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
            ->where('solicitud_detalles.id_solicitud', $solicitud)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
            ->get();
        //return $det_escuelas_preprimaria;
        
        if(isset($id_escolar2_racion)):
            $det_escuelas_primaria_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar2_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar2_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        else:
            $det_escuelas_primaria_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) {
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        endif;

        

        if(isset($id_escolar_expansion_racion)):
            $det_escuelas_preprimaria_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_expansion_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar_expansion_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        else:
            $det_escuelas_preprimaria_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) {
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        endif;

        if(isset($id_escolar2_expansion_racion)):
            $det_escuelas_primaria_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_expansion_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar_expansion_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        else:
            $det_escuelas_primaria_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) {
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        endif;

        if(isset($id_escolar_sllr)):
            $det_escuelas_preprimaria_sllr_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_sllr)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_sllr = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_sllr){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar_sllr);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_sllr)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

            $det_escuelas_primaria_sllr_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_sllr)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria_sllr = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar2_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar2_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_sllr)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

            
        else:
            $det_escuelas_preprimaria_sllr_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_sllr = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) {
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

            $det_escuelas_primaria_sllr_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria_sllr = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar2_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar2_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

           

        
            endif;

        //return $det_escuelas_primaria_sllr_enc;

        

        //return $det_escuelas_primaria_ex_enc;

        $det_escuelas_l_enc =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                DB::raw('raciones.id as idracion'),
                DB::raw('raciones.nombre as racion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $solicitud)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
            ->get(); 
        $det_escuelas_l = DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                DB::raw('bodegas.id as alimento_id'),
                DB::raw('bodegas.nombre as alimento'),
                DB::raw('alimentos_racion.cantidad as alimento_peso'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
            ) 
            ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j){
                $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
            })
            ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
            ->where('solicitud_detalles.id_solicitud', $solicitud)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
            ->get();

        if(isset($id_lideres_expansion_racion)):
            $det_escuelas_l_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get(); 
            $det_escuelas_l_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                ) 
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_lideres_expansion_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_lideres_expansion_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        else:
            $det_escuelas_l_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get(); 
            $det_escuelas_l_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                ) 
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        endif;

        $det_escuelas_v_d_enc =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                DB::raw('raciones.id as idracion'),
                DB::raw('raciones.nombre as racion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $solicitud)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
            ->get();        
        $det_escuelas_v_d = DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                DB::raw('bodegas.id as alimento_id'),
                DB::raw('bodegas.nombre as alimento'),
                DB::raw('alimentos_racion.cantidad as alimento_peso'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
            ) 
            ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j){
                $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
            })
            ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
            ->where('solicitud_detalles.id_solicitud', $solicitud)  
            ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)                
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
            ->get();

        if(isset($id_do_vo_expansion_racion)):
            $det_escuelas_v_d_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_expansion_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();        
                $det_escuelas_v_d_ex = DB::table('solicitud_detalles')
                    ->select(
                        DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                        DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                        DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                        DB::raw('bodegas.id as alimento_id'),
                        DB::raw('bodegas.nombre as alimento'),
                        DB::raw('alimentos_racion.cantidad as alimento_peso'),
                        DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                    ) 
                    ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_do_vo_expansion_racion){
                        $j->where("alimentos_racion.id_racion","=",$id_do_vo_expansion_racion);
                    })
                    ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                    ->where('solicitud_detalles.id_solicitud', $solicitud)  
                    ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_expansion_racion)                
                    ->where('solicitud_detalles.deleted_at', null)
                    ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                    ->get();
        else:
            $det_escuelas_v_d_ex_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();        
            $det_escuelas_v_d_ex = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                ) 
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j){
                    $j->on("alimentos_racion.id_racion","=","solicitud_detalles.tipo_de_actividad_alimentos");
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        endif;

        if(isset($id_escolar_ordinario_racion)):
            $det_escuelas_preprimaria_ordinario_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_ordinario = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_ordinario_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar_ordinario_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        endif;

        if(isset($id_escolar2_ordinario_racion)):
            $det_escuelas_primaria_ordinario_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_primaria_ordinario = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar2_ordinario_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar2_ordinario_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        endif;

        if(isset($id_lideres_ordinario_racion)):
            $det_escuelas_l_ordinario_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get(); 
            $det_escuelas_l_ordinario = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                ) 
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_lideres_ordinario_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_lideres_ordinario_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_lideres_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();
        endif;

        if(isset($id_do_vo_ordinario_racion)):
            $det_escuelas_v_d_ordinario_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();        
            $det_escuelas_v_d_ordinario = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(Distinct solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_de_docentes_y_voluntarios) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                ) 
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_do_vo_ordinario_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_do_vo_ordinario_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_do_vo_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        endif;


        
        
        $alimentos = Bodega::where('tipo_bodega', 1) 
            ->where('id_institucion', Auth::user()->id_institucion)
            ->get();

        $bodegas = Institucion::where('nivel', 2)->pluck('nombre','id');

        $consulta = Solicitud::where('id',$solicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::select('id','tipo_alimentos')->where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $raciones = Kit::select('id','tipo_kits')->where('id_institucion', Auth::user()->id_institucion)->get();
        endif;

        //return $det_escuelas_preprimaria_sllr_enc.' - '.$det_escuelas_preprimaria_sllr;
        

        $datos = [
            'escuelas' => $escuelas,
            'det_escuelas_preprimaria_enc' => $det_escuelas_preprimaria_enc,
            'det_escuelas_preprimaria' => $det_escuelas_preprimaria,
            'det_escuelas_primaria_enc' => $det_escuelas_primaria_enc,
            'det_escuelas_primaria' => $det_escuelas_primaria,
            'det_escuelas_preprimaria_ex_enc' => $det_escuelas_preprimaria_ex_enc,
            'det_escuelas_preprimaria_ex' => $det_escuelas_preprimaria_ex,
            'det_escuelas_preprimaria_sllr_enc' => $det_escuelas_preprimaria_sllr_enc,
            'det_escuelas_preprimaria_sllr' => $det_escuelas_preprimaria_sllr,      
            'det_escuelas_primaria_sllr_enc' => $det_escuelas_primaria_sllr_enc,
            'det_escuelas_primaria_sllr' => $det_escuelas_primaria_sllr,
            'det_escuelas_primaria_ex_enc' => $det_escuelas_primaria_ex_enc,
            'det_escuelas_primaria_ex' => $det_escuelas_primaria_ex,
            'det_escuelas_l_enc' => $det_escuelas_l_enc,
            'det_escuelas_l' => $det_escuelas_l,
            'det_escuelas_l_ex_enc' => $det_escuelas_l_ex_enc,
            'det_escuelas_l_ex' => $det_escuelas_l_ex,
            'det_escuelas_v_d_enc' => $det_escuelas_v_d_enc,
            'det_escuelas_v_d' => $det_escuelas_v_d,
            'det_escuelas_v_d_ex_enc' => $det_escuelas_v_d_ex_enc,
            'det_escuelas_v_d_ex' => $det_escuelas_v_d_ex,
            'det_escuelas_preprimaria_ordinario_enc' => $det_escuelas_preprimaria_ordinario_enc,
            'det_escuelas_preprimaria_ordinario' => $det_escuelas_preprimaria_ordinario,
            'det_escuelas_primaria_ordinario_enc' => $det_escuelas_primaria_ordinario_enc,
            'det_escuelas_primaria_ordinario' => $det_escuelas_primaria_ordinario,
            'det_escuelas_l_ordinario_enc' => $det_escuelas_l_ordinario_enc,
            'det_escuelas_l_ordinario' => $det_escuelas_l_ordinario,
            'det_escuelas_v_d_ordinario_enc' => $det_escuelas_v_d_ordinario_enc,
            'det_escuelas_v_d_ordinario' => $det_escuelas_v_d_ordinario,
            'alimentos' => $alimentos,
            'bodegas' => $bodegas,
            'raciones' => $raciones,
            'solicitud' => $solicitud
        ];

        return view('admin.solicitudes.solicitud_bodega',$datos);
    }

    public function getPruebaDatos($solicitud){
        $id_escolar_ordinario_racion = 12;

        $det_escuelas_preprimaria_ordinario_enc =  DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM(solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('raciones.id as idracion'),
                    DB::raw('raciones.nombre as racion'),
                )
                ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre', 'raciones.id')
                ->get();
            $det_escuelas_preprimaria_ordinario = DB::table('solicitud_detalles')
                ->select(
                    DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                    DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                    DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                    DB::raw('bodegas.id as alimento_id'),
                    DB::raw('bodegas.nombre as alimento'),
                    DB::raw('alimentos_racion.cantidad as alimento_peso'),
                    DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as racion'),
                )
                ->join(DB::RAW("(SELECT id_racion, id_alimento, cantidad FROM alimentos_raciones GROUP BY id_racion, id_alimento, cantidad) as alimentos_racion"), function($j) use($id_escolar_ordinario_racion){
                    $j->where("alimentos_racion.id_racion","=",$id_escolar_ordinario_racion);
                })
                ->join('bodegas', 'bodegas.id', 'alimentos_racion.id_alimento')
                ->where('solicitud_detalles.id_solicitud', $solicitud)  
                ->where('solicitud_detalles.tipo_de_actividad_alimentos', $id_escolar_ordinario_racion)                
                ->where('solicitud_detalles.deleted_at', null)
                ->groupBy('solicitud_detalles.id_escuela', 'solicitud_detalles.tipo_de_actividad_alimentos', 'bodegas.id', 'bodegas.nombre', 'alimentos_racion.cantidad')
                ->get();

        return '<h1> Encabezado: </h1> <br/>'.$det_escuelas_preprimaria_ordinario_enc.'<h1> Detalles : </h1> <br/>'.$det_escuelas_preprimaria_ordinario;
    }

    public function postSolicitudABodegaPrimaria(Request $request){
        $reglas = [

    	];
    	$mensajes = [

    	];

        $validator = Validator::make($request->all(), $reglas, $mensajes);
    	if($validator->fails()):
    		return back()->withErrors($validator)->with('messages', 'Se ha producido un error.')->with('typealert', 'danger');
        else: 
            DB::beginTransaction();
                $tipo_alimentacion = $request->input('tipo_racion');                
                $racion = Racion::select('tipo_alimentos')->where('id',$tipo_alimentacion)->first();

                switch($racion->tipo_alimentos):
                    case 'solicitud_comida_escolar':
                        
                        $total_beneficiarios = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_estudiantes');
                        $total_raciones = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_raciones_de_estudiantes');
                        
                    break;

                    case 'solicitud_racion_psc':
                        $total_beneficiarios = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_docentes_y_voluntarios');
                        $total_raciones = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_raciones_de_docentes_y_voluntarios');
                        
                    break;

                    case 'lideres_de_alimentacion_escolar':
                        $total_beneficiarios = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_personas');
                        $total_raciones = SolicitudDetalles::where('id_solicitud', $request->input('idSolicitud'))->sum('total_de_raciones');
                    break;


                endswitch;

                $s = new SolicitudBodegaPrimaria;
                $s->fecha = Carbon::now()->format('Y-m-d');
                $s->id_bodega_primaria = $request->input('id_bodega_primaria');
                $s->beneficiarios = $total_beneficiarios;
                $s->raciones_solicitadas = $total_raciones;
                $s->id_socio_solicitante = Auth::user()->id_institucion;
                $s->estado = 1;
                $s->id_institucion = Auth::user()->id_institucion;
                $s->save();

                $idinsumo=$request->get('idinsumo');
                $cantidad=$request->get('cantidad');
                $idmedida=$request->get('idmedida');
                $cont=0;

                while ($cont<count($idinsumo)) {
                    $detalle=new SolicitudBodegaPrimariaDetalle();
                    $detalle->id_solicitud_bodega_primaria = $s->id;
                    $detalle->id_insumo_bodega_socio = $idinsumo[$cont];
                    $insumoBodegaSocioNombre = Bodega::where('id',$idinsumo[$cont])->where('tipo_bodega',1)->where('id_institucion', Auth::user()->id_institucion)->first();                
                    $insumoIDBPrimaria = Bodega::where('nombre',$insumoBodegaSocioNombre->nombre)->where('tipo_bodega',0)->where('id_institucion', $s->id_bodega_primaria)->first();
                    $detalle->tipo_racion = $tipo_alimentacion;
                    $detalle->id_insumo_bodega_primaria = $insumoIDBPrimaria->id;
                    $detalle->no_unidades = $cantidad[$cont];
                    $detalle->id_unidad_medida = $idmedida[$cont];
                    $detalle->save();
                    $cont=$cont+1;
                }

            DB::commit();

            if($s->save()):
                $b = new Bitacora;
                $b->accion = 'Registro de solicitud de insumos a bodega primaria';
                $b->id_usuario = Auth::id();
                $b->save();

                return back()->with('messages', '¡Solicitud registrada y guardada con exito!.')
                    ->with('typealert', 'success');
    		endif;
        endif;
    }

    public function postDespacharEscolares(Request $request){
        $escuela = Escuela::where('id', $request->input('idEscuela'))->first();

        $saldos = DB::table('bodegas as b')
        ->select(
            DB::RAW('b.id as id_insumo'),
            DB::RAW('bi_det.pl as pl'),
            DB::RAW('bi_det.bubd as bubd'),
            DB::RAW('(bi_det.no_unidades - bi_det.no_unidades_usadas) as disponible')
        )
        ->Join('bodegas_ingresos_detalles as bi_det', 'bi_det.id_insumo', 'b.id')
        ->where('b.id_institucion', Auth::user()->id_institucion)  
        ->where('b.tipo_bodega', 1) 
        ->orderBy('bi_det.bubd')
        ->get();

        

        
        //return $alimentos;
        //return $request->all();
        $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Escolar"):
                    $id_escolar_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2"):
                    $id_escolar2_racion = $r->id;
                endif;

                if($r->nombre =="Escolar expansión"):
                    $id_escolar_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 expansión"):
                    $id_escolar2_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar SLLR"):
                    $id_escolar_sllr = $r->id;
                endif;

                if($r->nombre =="Escolar Ordinario"):
                    $id_escolar_ordinario_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 Ordinario"):
                    $id_escolar2_ordinario_racion = $r->id;
                endif;

            endforeach;
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Escolar"):
                    $id_escolar_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2"):
                    $id_escolar2_racion = $r->id;
                endif;

                if($r->nombre =="Escolar expansión"):
                    $id_escolar_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Escolar2 expansión"):
                    $id_escolar2_expansion_racion = $r->id;
                endif;

            endforeach;
        endif;
        
        //return $id_escolar2_racion.' - '.$id_escolar2_expansion_racion;

        $descarga_pre =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_pre_primaria_a_tercero_primaria) as total_beneficiarios'),
                DB::raw('raciones.nombre as racion'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as tipo_alimentacion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $request->input('idSolicitud'))  
            ->where('solicitud_detalles.id_escuela', $request->input('idEscuela'))   
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [$id_escolar_racion,$id_escolar_expansion_racion,$id_escolar_sllr,$id_escolar2_racion,$id_escolar_ordinario_racion,$id_escolar2_ordinario_racion])            
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre','solicitud_detalles.tipo_de_actividad_alimentos')
            ->get();

        $descarga_pri =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_cuarto_a_sexto) as total_beneficiarios'),
                DB::raw('raciones.nombre as racion'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as tipo_alimentacion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $request->input('idSolicitud'))  
            ->where('solicitud_detalles.id_escuela', $request->input('idEscuela'))   
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [$id_escolar_racion,$id_escolar2_racion,$id_escolar_expansion_racion,$id_escolar2_expansion_racion,$id_escolar_ordinario_racion,$id_escolar2_ordinario_racion])         
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre','solicitud_detalles.tipo_de_actividad_alimentos')
            ->get();  

        //return '<h1> Preprimaria </h1> </br>'.$descarga_pre.'</br> <h1> Primaria </h1> </br>'.$descarga_pri;

        
        
        foreach($descarga_pre as $d_pre):
            $dias_pre = $d_pre->dias;
            $beneficiarios_pre = $d_pre->total_beneficiarios;
            $tipo_act_ali_pre = $d_pre->tipo_alimentacion;
        endforeach;

        foreach($descarga_pri as $d_pri):
            $dias_pri = $d_pri->dias;
            $beneficiarios_pri = $d_pri->total_beneficiarios;
        endforeach;

        //return '<h1> Preprimaria </h1> </br>'.$dias_pre.'-'.$beneficiarios_pre.'-'.$tipo_act_ali_pre.'</br> <h1> Primaria </h1> </br>'.$dias_pri.'-'.$beneficiarios_pri;

        $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $racion = Racion::with('alimentos')->where('id', '=', $tipo_act_ali_pre)->where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $racion = Kit::with('alimentos')->where('id', '=', $tipo_act_ali_pre)->where('id_institucion', Auth::user()->id_institucion)->get();
        endif;


        foreach($racion  as $r):
            $actividad = $r->id;
            $alimentos = $r->alimentos;
        endforeach;

        if($id_escolar_racion == $tipo_act_ali_pre ||  $tipo_act_ali_pre == 10):
            $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
            foreach($consulta as $c):
                $tipo_insumo = $c->tipo_insumos;
            endforeach;
            
            if($tipo_insumo == "1"):
                $racion2 = Racion::with('alimentos')->where('id', '=', $id_escolar2_racion)->where('id_institucion', Auth::user()->id_institucion)->get();     
            else:
                $racion2 = Kit::with('insumos')->where('id', '=', $id_escolar2_racion)->where('id_institucion', Auth::user()->id_institucion)->get();     
            endif;
        elseif($tipo_act_ali_pre == 12):
            $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
            foreach($consulta as $c):
                $tipo_insumo = $c->tipo_insumos;
            endforeach;
            
            if($tipo_insumo == "1"):
                $racion2 = Racion::with('alimentos')->where('id', '=', $id_escolar2_ordinario_racion)->where('id_institucion', Auth::user()->id_institucion)->get();     
            else:
                $racion2 = Kit::with('insumos')->where('id', '=', $id_escolar2_ordinario_racion)->where('id_institucion', Auth::user()->id_institucion)->get();     
            endif;
        else:
            
            $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
            foreach($consulta as $c):
                $tipo_insumo = $c->tipo_insumos;
            endforeach;
            
            if($tipo_insumo == "1"):
                $racion2 = Racion::with('alimentos')->where('id', '=', $id_escolar2_expansion_racion)->where('id_institucion', Auth::user()->id_institucion)->get();
            else:
                $$racion2 = Kit::with('insumos')->where('id', '=', $id_escolar2_expansion_racion)->where('id_institucion', Auth::user()->id_institucion)->get();
            endif;
        endif;

        foreach($racion2  as $r2):
            $alimentos1 = $r2->alimentos;
        endforeach;

       //return $alimentos.' \nsegundos alimentos\n'.$alimentos1; 

        //return Carbon::now()->format('Y-m-d');
        $pls = BodegaIngresoDetalle::select('id','pl')->whereRaw('(no_unidades - no_unidades_usadas) > 0')->get();

        $be = new BodegaEgreso;
        $be->fecha = Carbon::now()->format('Y-m-d');
        $be->tipo_documento = 1;
        $be->no_documento = $request->input('no_boleta');
        $be->id_solicitud_despacho = $request->input('idSolicitud');
        $be->id_escuela_despacho = $request->input('idEscuela');
        $be->tipo_racion = $actividad;
        $be->participantes = $beneficiarios_pre + $beneficiarios_pri;
        $be->tipo_bodega = 1;
        $be->id_institucion = Auth::user()->id_institucion;
        $be->save();

        $cont=0;

        while ($cont<count($alimentos)) {
            $detalle=new BodegaEgresoDetalle();
            $detalle->id_egreso = $be->id;
            $detalle->id_insumo = $alimentos[$cont]->id_alimento;        
            $detalle->pl = 0;   

            if( $alimentos[$cont]->id_alimento != 26 ):
                $detalle->no_unidades =  number_format( ((($dias_pre*$beneficiarios_pre*$alimentos[$cont]->cantidad)/453.59237)/100), 2, '.', ',' ) + number_format( ((($dias_pri*$beneficiarios_pri*$alimentos1[$cont]->cantidad)/453.59237)/100), 2, '.', ',' ) ;
            else:
                $detalle->no_unidades =  number_format( ((($dias_pre*$beneficiarios_pre*$alimentos[$cont]->cantidad)/453.59237)/100), 2, '.', ',' ) + number_format( ((($dias_pri*$beneficiarios_pri*$alimentos1[$cont]->cantidad)/453.59237)/100), 2, '.', ',' ) ;
            endif;
            $detalle->save();
            $cont=$cont+1;
        }

        
        $detalles_actuales = BodegaEgresoDetalle::where('pl', 0)->where('id_egreso',$be->id)->get();

        foreach($detalles_actuales as $det):
            foreach($pls as $pl):
                if($pl->id == $det->id_insumo):
                    $det->pl = $pl->pl;   
                endif;
            endforeach;
            $det->save();
        endforeach;

        $detalles_recientes = BodegaEgresoDetalle::select('id_insumo','pl','no_unidades')->where('id_egreso',$be->id)->get();

       foreach($detalles_recientes as $det_rec):
                DB::table('bodegas_ingresos_detalles')
                    ->where('id_insumo', $det_rec->id_insumo)
                    ->where('pl', $det_rec->pl)
                    ->increment('no_unidades_usadas', $det_rec->no_unidades);
                DB::table('bodegas')
                    ->where('id', $det_rec->id_insumo)
                    ->decrement('saldo', $det_rec->no_unidades);
        endforeach;

        

        $b = new Bitacora;
        $b->accion = 'Despacho automatico de raciones escolares para la escuela: '.$escuela->codigo.' '.$escuela->nombre.' correspondiente a la solicitud no. '.$request->input('idSolictiud');
        $b->id_usuario = Auth::id();
        $b->save();

        return back()->with('messages', '¡Despacho realizado con exito!.')
            ->with('typealert', 'success');
                 
    }

    public function postDespacharLideres(Request $request){
        $escuela = Escuela::where('id', $request->input('idEscuela'))->first();

        $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):            
    
                if($r->nombre =="Líderes"):
                    $id_lideres_racion = $r->id;
                endif;
    
                if($r->nombre =="Líderes expansión"):
                    $id_lideres_expansion_racion = $r->id;
                endif;

                if($r->nombre =="Líderes Ordinario"):
                    $id_lideres_ordinario_racion = $r->id;
                endif;
            endforeach;
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):            
    
                if($r->nombre =="Líderes"):
                    $id_lideres_racion = $r->id;
                endif;
    
                if($r->nombre =="Líderes expansión"):
                    $id_lideres_expansion_racion = $r->id;
                endif;
            endforeach;
        endif;

       

        $saldos = DB::table('bodegas as b')
        ->select(
            DB::RAW('b.id as id_insumo'),
            DB::RAW('bi_det.pl as pl'),
            DB::RAW('bi_det.bubd as bubd'),
            DB::RAW('(bi_det.no_unidades - bi_det.no_unidades_usadas) as disponible')
        )
        ->Join('bodegas_ingresos_detalles as bi_det', 'bi_det.id_insumo', 'b.id')
        ->where('b.id_institucion', Auth::user()->id_institucion)  
        ->where('b.tipo_bodega', 1) 
        ->orderBy('bi_det.bubd')
        ->get();

        
        //return $saldos;

        $descarga =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_personas) as total_beneficiarios'),
                DB::raw('raciones.nombre as racion'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as tipo_alimentacion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $request->input('idSolicitud'))  
            ->where('solicitud_detalles.id_escuela', $request->input('idEscuela'))   
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [$id_lideres_racion,$id_lideres_expansion_racion,$id_lideres_ordinario_racion])           
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre','solicitud_detalles.tipo_de_actividad_alimentos')
            ->get();
            //return $descarga;
        
        foreach($descarga as $d):
            $dias = $d->dias;
            $beneficiarios = $d->total_beneficiarios;
            $tipo_alimentacion = $d->tipo_alimentacion;
        endforeach;

        $consulta = Solicitud::where('id',$request->input('idSolicitud'))->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $racion = Racion::with('alimentos')->where('id', '=', $tipo_alimentacion)->where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $racion = Kit::with('insumos')->where('id', '=', $tipo_alimentacion)->where('id_institucion', Auth::user()->id_institucion)->get();
        endif;

        
        //return $racion;
        foreach($racion  as $r):
            $alimentos = $r->alimentos;
        endforeach;

        //return Carbon::now()->format('Y-m-d');

        $be = new BodegaEgreso;
        $be->fecha = Carbon::now()->format('Y-m-d');
        $be->tipo_documento = 1;
        $be->no_documento = $request->input('no_boleta');
        $be->id_solicitud_despacho = $request->input('idSolicitud');
        $be->id_escuela_despacho = $request->input('idEscuela');
        $be->tipo_racion = $tipo_alimentacion;
        $be->participantes = $beneficiarios;
        $be->tipo_bodega = 1;
        $be->id_institucion = Auth::user()->id_institucion;
        $be->save();

        $pls = BodegaIngresoDetalle::select('id','pl')->whereRaw('(no_unidades - no_unidades_usadas) > 0')->get();
        $cont=0;

        while ($cont<count($alimentos)) {
            $unidades_despachar = number_format( ((($dias*$beneficiarios*$alimentos[$cont]->cantidad)/100)), 2, '.', ',' ) ;
            $detalle=new BodegaEgresoDetalle();
            $detalle->id_egreso = $be->id;
            $detalle->id_insumo = $alimentos[$cont]->id_alimento;        
            $detalle->pl = 0;  
            $detalle->no_unidades = $unidades_despachar; 
            $detalle->save();
            $cont=$cont+1;
        }

        $detalles_actuales = BodegaEgresoDetalle::where('pl', 0)->where('id_egreso',$be->id)->get();

        foreach($detalles_actuales as $det):
            foreach($pls as $pl):
                if($pl->id == $det->id_insumo):
                    $det->pl = $pl->pl;   
                endif;
            endforeach;
            $det->save();
        endforeach;

        $detalles_recientes = BodegaEgresoDetalle::select('id_insumo','pl','no_unidades')->where('id_egreso',$be->id)->get();

       foreach($detalles_recientes as $det_rec):
                DB::table('bodegas_ingresos_detalles')
                    ->where('id_insumo', $det_rec->id_insumo)
                    ->where('pl', $det_rec->pl)
                    ->increment('no_unidades_usadas', $det_rec->no_unidades);
                DB::table('bodegas')
                    ->where('id', $det_rec->id_insumo)
                    ->decrement('saldo', $det_rec->no_unidades);
        endforeach;

        $b = new Bitacora;
        $b->accion = 'Despacho automatico de raciones de lideres para la escuela: '.$escuela->codigo.' '.$escuela->nombre.' correspondiente a la solicitud no. '.$request->input('idSolictiud');
        $b->id_usuario = Auth::id();
        $b->save();

        return back()->with('messages', '¡Despacho realizado con exito!.')
            ->with('typealert', 'success');
                 
    }

    public function postDespacharVoluntarios(Request $request){
        $idSolicitud = $request->input('idSolicitud');
        $idEscuela = $request->input('idEscuela');
        $escuela = Escuela::where('id', $idEscuela)->first();

        $consulta = Solicitud::where('id',$idSolicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $raciones = Racion::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Voluntarios"):
                    $id_do_vo_racion = $r->id;
                endif;     
                
                if($r->nombre =="Voluntarios expansión"):
                    $id_do_vo_expansion_racion = $r->id;
                endif;
                
                if($r->nombre =="Voluntario Ordinario"):
                    $id_do_vo_ordinario_racion = $r->id;
                endif;
            endforeach;
        else:
            $raciones = Kit::where('id_institucion', Auth::user()->id_institucion)->get();
            foreach($raciones as $r):
                if($r->nombre =="Voluntarios"):
                    $id_do_vo_racion = $r->id;
                endif;     
                
                if($r->nombre =="Voluntarios expansión"):
                    $id_do_vo_expansion_racion = $r->id;
                endif; 
            endforeach;
        endif;

        

        $saldos = DB::table('bodegas as b')
        ->select(
            DB::RAW('b.id as id_insumo'),
            DB::RAW('bi_det.pl as pl'),
            DB::RAW('bi_det.bubd as bubd'),
            DB::RAW('(bi_det.no_unidades - bi_det.no_unidades_usadas) as disponible')
        )
        ->Join('bodegas_ingresos_detalles as bi_det', 'bi_det.id_insumo', 'b.id')
        ->where('b.id_institucion', Auth::user()->id_institucion)  
        ->where('b.tipo_bodega', 1) 
        ->orderBy('bi_det.bubd')
        ->get();
        //return $saldos;
        
        //return $saldos;
        //return $request->all();

        $descarga =  DB::table('solicitud_detalles')
            ->select(
                DB::raw('solicitud_detalles.id_escuela as escuela_id'),
                DB::raw('SUM( solicitud_detalles.dias_de_solicitud) as dias'),
                DB::raw('SUM(Distinct solicitud_detalles.total_de_personas) as total_beneficiarios'),
                DB::raw('raciones.nombre as racion'),
                DB::raw('solicitud_detalles.tipo_de_actividad_alimentos as tipo_alimentacion'),
            )
            ->join('raciones', 'raciones.id', 'solicitud_detalles.tipo_de_actividad_alimentos')
            ->where('solicitud_detalles.id_solicitud', $idSolicitud)  
            ->where('solicitud_detalles.id_escuela', $idEscuela)   
            ->whereIn('solicitud_detalles.tipo_de_actividad_alimentos', [$id_do_vo_racion,$id_do_vo_expansion_racion,$id_do_vo_ordinario_racion])           
            ->where('solicitud_detalles.deleted_at', null)
            ->groupBy('solicitud_detalles.id_escuela', 'raciones.nombre','solicitud_detalles.tipo_de_actividad_alimentos')
            ->get();
            //return $descarga;
        
        foreach($descarga as $d):
            $dias = $d->dias;
            $beneficiarios = $d->total_beneficiarios;
            $tipo_alimentacion = $d->tipo_alimentacion;
        endforeach;

        $consulta = Solicitud::where('id',$idSolicitud)->get();
        foreach($consulta as $c):
            $tipo_insumo = $c->tipo_insumos;
        endforeach;
        
        if($tipo_insumo == "1"):
            $racion = Racion::with('alimentos')->where('id', '=', $tipo_alimentacion)->where('id_institucion', Auth::user()->id_institucion)->get();
        else:
            $$racion = Kit::with('insumos')->where('id', '=', $tipo_alimentacion)->where('id_institucion', Auth::user()->id_institucion)->get();
        endif;

        
        foreach($racion  as $r):
            $actividad = $r->id;
            $alimentos = $r->alimentos;
        endforeach;

        //return Carbon::now()->format('Y-m-d');

        $be = new BodegaEgreso;
        $be->fecha = Carbon::now()->format('Y-m-d');
        $be->tipo_documento = 1;
        $be->no_documento = $request->input('no_boleta');
        $be->id_solicitud_despacho = $idSolicitud;
        $be->id_escuela_despacho = $idEscuela;
        $be->tipo_racion =  $tipo_alimentacion;
        $be->participantes = $beneficiarios;
        $be->tipo_bodega = 1;
        $be->id_institucion = Auth::user()->id_institucion;
        $be->save();
        $pls = BodegaIngresoDetalle::select('id','pl')->whereRaw('(no_unidades - no_unidades_usadas) > 0')->get();
        $cont = 0;
        while ($cont<count($alimentos)) {
            $detalle=new BodegaEgresoDetalle();
            $detalle->id_egreso = $be->id;
            $detalle->id_insumo = $alimentos[$cont]->id_alimento;        
            
            $detalle->pl = 0;
            
            $detalle->no_unidades =  number_format( ((($dias*$beneficiarios*$alimentos[$cont]->cantidad)/100)), 2, '.', ',' ) ;
            $detalle->save();
            $cont=$cont+1;
        }

        $detalles_actuales = BodegaEgresoDetalle::where('pl', 0)->where('id_egreso',$be->id)->get();

        foreach($detalles_actuales as $det):
            foreach($pls as $pl):
                if($pl->id == $det->id_insumo):
                    $det->pl = $pl->pl;   
                endif;
            endforeach;
            $det->save();
        endforeach;

        $detalles_recientes = BodegaEgresoDetalle::select('id_insumo','pl','no_unidades')->where('id_egreso',$be->id)->get();

       foreach($detalles_recientes as $det_rec):
                DB::table('bodegas_ingresos_detalles')
                    ->where('id_insumo', $det_rec->id_insumo)
                    ->where('pl', $det_rec->pl)
                    ->increment('no_unidades_usadas', $det_rec->no_unidades);
                DB::table('bodegas')
                    ->where('id', $det_rec->id_insumo)
                    ->decrement('saldo', $det_rec->no_unidades);
        endforeach;
        

        $b = new Bitacora;
        $b->accion = 'Despacho automatico de raciones de voluntarios para la escuela: '.$escuela->codigo.' '.$escuela->nombre.' correspondiente a la solicitud no. '.$idSolicitud;
        $b->id_usuario = Auth::id();
        $b->save();

        return back()->with('messages', '¡Despacho realizado con exito!.')
            ->with('typealert', 'success');
                 
    }


}
