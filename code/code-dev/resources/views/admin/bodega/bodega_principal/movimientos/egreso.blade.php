@extends('admin.plantilla.master')
@section('title','Bodega Primaria')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href=""><i class="fa-solid fa-warehouse"></i> Bodega Primaria</a></li>
    <li class="breadcrumb-item"><a href="{{ url('/admin/bodega_primaria/ingresos') }}"><i class="fa-solid fa-calculator"></i> Registro de Egresos</a></li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card ">

                <div class="card-header">
                    <h2 class="title"><i class="fas fa-plus-circle"></i><strong> Registro De Egresos A Bodega</strong></h2>
                    
                </div>

                <div class="card-body">
                    {!! Form::open(['url' => '/admin/bodega_principal/insumo/egresos', 'files' => true]) !!}
                        <h5><strong>Información de Egreso</strong></h5>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Fecha de Ingreso: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-layer-group"></i></span>
                                    {!! Form::date('fecha_egreso', \Carbon\Carbon::now(), ['class'=>'form-control']) !!}
                                </div>
                            </div>                            

                            <div class="col-md-4">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Tipo De Documento: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-layer-group"></i></span>
                                    {!! Form::select('tipo_documento', ['0'=>'Guia de Transporte Terrestre','1'=>'Otros'],0,['class'=>'form-select']) !!}
                                </div>
                            </div>  

                            <div class="col-md-4">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> No. De Documento: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-keyboard"></i></span>
                                    {!! Form::text('no_documento', null, ['class'=>'form-control']) !!}
                                </div>
                            </div>
 
                            <div class="col-md-4 mtop16">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Socio/Institución A Trabajar: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-layer-group"></i></span>

                                    <select name="id_socio" id="id_socio" style="width: 78%">
                                        @foreach($socios as $s)
                                            <option value="{{ $s->id }}">{{ $s->nombre}}</option>
                                        @endforeach
                                    </select>        
                                     
                                    <a href="#" class="btn btn-sm btn-info " id="btn_buscar_socios_solicitudes_despacho" data-toogle="tooltrip" data-placement="top" title="Buscar" ><i class="fas fa-search"></i> </a>
                                    <a href="{{ url('/admin/bodega_principal/insumo/egresos') }}" class="btn btn-sm btn-warning " data-toogle="tooltrip" data-placement="top" title="Borrar" ><i class="fa-solid fa-eraser"></i> </a>
                                </div>
                            </div>

                            <div class="col-md-8 mtop16">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Solicitud a Despachar: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-layer-group"></i></span>

                                    <select name="id_solicitud" id="id_solicitud" style="width: 93%">
                                        
                                    </select>            
                                </div>
                            </div>

                            

                        </div>

                        <div class="row mtop16">
                            <h5 ><strong>Cálculos de Alimentos en la Solicitud</strong></h5>
                            <div id="div-msg-det-escuelas">
                               <!-- <p style="color: red; text-aling: center;"><b>Seleccione una escuela </b></p>-->
                            </div>

                            <div id="div-res-det-escuelas" style="hidden:true;">
                            <p style="color: red; text-aling: center;"><b>funcionaaaaaaa </b></p>
                            </div>
                            
                        </div>

                        <hr />
                        <h5><strong>Detalle De La Carga</strong></h5>

                        <div class="row">
                            <div class="col-md-3">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Insumo a Descargar: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-layer-group"></i></span>
                                    <select name="idinsumoEgresos" id="idinsumoEgresos" style="width: 83%" >
                                        @foreach($insumos as $i)
                                            <option value=""></option>
                                            <option value="{{ $i->id }}">{{ $i->nombre }}</option>
                                        @endforeach
                                    </select> 

                
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> PL Disponible: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-keyboard"></i></span>
                                    <select name="pl_disponible" id="pl_disponible" style="width: 90%">
                                        
                                    </select>  
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> Best Used By/Before Date: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-keyboard"></i></span>
                                    {!! Form::date('bubd', null, ['class'=>'form-control', 'id' => 'bubd', 'readonly']) !!}
                                </div> 
                            </div>

                            <div class="col-md-2">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> No. Unidades Disponible: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-keyboard"></i></span>
                                    {!! Form::number('no_unidades_disponibles', null, ['class'=>'form-control', 'id' => 'no_unidades_disponibles', 'readonly']) !!}
                                </div> 
                            </div>

                            <div class="col-md-2">
                                <label for="name"> <strong><sup ><i class="fa-solid fa-triangle-exclamation"></i></sup> No. Unidades A Utilizar: </strong></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-keyboard"></i></span>
                                    {!! Form::number('no_unidades', null, ['class'=>'form-control', 'id' => 'no_unidades', 'min' => '1']) !!}
                                </div>
                            </div>

                            <div class="col-md-1">
                                <button type="button" id="bt_add" class="btn btn-primary"> Agregar</button>
                            </div>                           
                        </div>

                        <div class="row">
                            <div class="card-body table-responsive">
                                <table id="detalles" class= "table table-striped table-bordered table-condensed table-hover">
                                    <thead style="background-color: #c3f3ea">
                                        <th>ELIMINAR</th>
                                        <th>INSUMO</th>
                                        <th>PL</th>
                                        <th>NO UNIDADES</th>
                                    </thead>

                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="guardar">
                            {!! Form::submit('Guardar', ['class'=>'btn btn-success mtop16']) !!}
                            <a href="{{ url('/admin/escuelas') }}" class="btn btn-secondary mtop16">Regresar</a>
                        </div>

                    {!! Form::close() !!}
                </div>

            </div>
            
        </div>

    </div>

</div>

<script>
    


    $(document).ready(function(){
        $('#bt_add').click(function(){
        agregar();
        });
    });

    var cont=0;
    total=0;
    subtotal=[];
    $("#guardar").hide();

    function agregar(){
        idinsumo=$("#idinsumoEgresos").val();
        insumo=$("#idinsumoEgresos option:selected").text();
        idpl=$("#pl_disponible").val();
        pl=$("#pl_disponible option:selected").text();
        no_unidades_disponibles=$("#no_unidades_disponibles").val();
        no_unidades=$("#no_unidades").val();

        if (idinsumo!="" && no_unidades > 0 &&  ( no_unidades_disponibles - no_unidades) >= 0  ){
            var fila='<tr class="selected" id="fila'+cont+'"><td><button type="button" class="btn btn-warning" onclick="eliminar('+cont+');">X</button></td><td><input type="hidden" name="idinsumo[]" value="'+idinsumo+'">'+insumo+'</td><td><input type="hidden" name="idpl[]" value="'+idpl+'">'+pl+'</td><td><input type="number" name="no_unidades[]" value="'+no_unidades+'"></td></tr>';
            cont++;
            limpiar();
            evaluar();
            $('#detalles').append(fila);
        }else{
            alert("Error al ingresar el detalle del ingreso, revise los datos del insumo a registrar")
        }
        
    }

    function limpiar(){
        $("#pl").val("");
        $("#no_unidades").val("");
        $("#unidad_medida").val(""); 
    }

    function evaluar()
    {
        if (cont >0){
            $("#guardar").show();
        }else{
            $("#guardar").hide();
        }
    }

    function eliminar(index){
        $("#fila" + index).remove();
        cont--;
        evaluar();
    }



</script>

@endsection