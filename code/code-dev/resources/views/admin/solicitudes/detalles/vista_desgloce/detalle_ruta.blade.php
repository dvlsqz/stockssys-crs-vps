<div class="card ">

    <div class="card-header">                
        <h2 class="card-title"><strong><i class="fa-solid fa-road-circle-exclamation"></i> Desglose de la Ruta: {{$ruta->ubicacion->nomenclatura.'0'.$ruta->correlativo}}</strong></h2>

    </div>

    <div class="card-body " style="text-align:center;  overflow-y: scroll; line-height: 1em; height:325px;">  
        @php($total_raciones = 0) @php($total_peso_ruta_quintales = 0)
        @if(count($detalles_ruta_escuelas) > 0)                                    
            @foreach($detalles_ruta_escuelas as $det)
                <p class="mtop16"> <b>{{$loop->iteration}}. {{$det->escuela}} - Total Raciones: {{number_format($det->total_raciones)}} </b> </p> @php($total_raciones = $total_raciones +$det->total_raciones )
                <div class="row mtop16">
                    <div class="col-md-3">
                        <b style="color:blue;">Niños Pre Primaria a Tercero Primaria</b><br> 

                        @foreach($det_escuelas_preprimaria_enc as $det_pre_enc)
                            @php($id_racion_escolar = 0)
                            @if($det_pre_enc->escuela_id == $det->escuela_id)
                                @php($id_racion_escolar = $det_pre_enc->idracion)
                            @endif
                        @endforeach
                        @php($total_peso_ruta = 0) 

                        @if($id_racion_escolar == 1)
                            @foreach($det_escuelas_preprimaria as $det1)
                                @if($det1->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det1->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det1->total_ninos }} <br>
                                        <b>Raciones:</b> {{ number_format($det1->dias * $det1->total_ninos)}} <br>
                                        <b>Peso de Raciones en Gramos:</b> {{ number_format( ($det1->dias * $det1->total_ninos * $det1->peso_racion), 2, '.', ',' )}} <br> @php($total_peso_ruta = $total_peso_ruta + ($det1->dias * $det1->total_ninos * $det1->peso_racion)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det1->dias * $det1->total_ninos * $det1->peso_racion)/453.59237)/100, 2, '.', ',' ) }} <br>  @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det1->dias * $det1->total_ninos * $det1->peso_racion)/453.59237)/100))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det1->dias * $det1->total_ninos * $det1->peso_racion)/1000)/50, 2, '.', ',' ) }}
                                    </p>  
                                @endif

                            @endforeach
                        @else
                            <span style="color: red;"> Datos de ración de expansion</span>
                            @foreach($det_escuelas_preprimaria_ex as $det1_ex)
                                @if($det1_ex->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det1_ex->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det1_ex->total_ninos }} <br>
                                        <b>Raciones:</b> {{ number_format($det1_ex->dias * $det1_ex->total_ninos)}} <br>
                                        <b>Peso de Raciones en Gramos:</b> {{ number_format( ($det1_ex->dias * $det1_ex->total_ninos * $det1_ex->peso_racion), 2, '.', ',' )}} <br> @php($total_peso_ruta = $total_peso_ruta + ($det1_ex->dias * $det1_ex->total_ninos * $det1_ex->peso_racion)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det1_ex->dias * $det1_ex->total_ninos * $det1_ex->peso_racion)/453.59237)/100, 2, '.', ',' ) }} <br>  @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det1_ex->dias * $det1_ex->total_ninos * $det1_ex->peso_racion)/453.59237)/100))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det1_ex->dias * $det1_ex->total_ninos * $det1_ex->peso_racion)/1000)/50, 2, '.', ',' ) }}
                                    </p>  
                                @endif

                            @endforeach

                            
                        @endif
                    </div>
                    <div class="col-md-3">
                        <b style="color:blue;">Niños Cuarto Primaria a Sexto Primaria</b><br>
                        @foreach($det_escuelas_primaria_enc as $det_pri_enc)
                            @php($id_racion_escolar2 = 0)
                            @if($det_pri_enc->escuela_id == $det->escuela_id)
                                @php($id_racion_escolar2 = $det_pri_enc->idracion)
                            @endif
                        @endforeach

                        @if($id_racion_escolar2 == 1)
                            @foreach($det_escuelas_primaria as $det2)
                                @if($det2->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det2->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det2->total_ninos }} <br>
                                        <b>Raciones:</b> {{ number_format($det2->dias * $det2->total_ninos)}} <br>
                                        <b>Peso de Raciones en Gramos:</b> {{ number_format( ($det2->dias * $det2->total_ninos * $det2->peso_racion), 2, '.', ',' )}} <br> @php($total_peso_ruta = $total_peso_ruta + ($det2->dias * $det2->total_ninos * $det2->peso_racion)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det2->dias * $det2->total_ninos * $det2->peso_racion)/453.59237)/100, 2, '.', ',' ) }} <br> @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det2->dias * $det2->total_ninos * $det2->peso_racion)/453.59237)/100))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det2->dias * $det2->total_ninos * $det2->peso_racion)/1000)/50, 2, '.', ',' ) }}
                                    </p>

                                @endif
                            @endforeach
                        @else
                            <span style="color: red;"> Datos de ración de expansion</span>
                            @foreach($det_escuelas_primaria_ex as $det2_ex)
                                @if($det2_ex->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det2_ex->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det2_ex->total_ninos }} <br>
                                        <b>Raciones:</b> {{ number_format($det2_ex->dias * $det2_ex->total_ninos)}} <br>
                                        <b>Peso de Raciones en Gramos:</b> {{ number_format( ($det2_ex->dias * $det2_ex->total_ninos * $det2_ex->peso_racion), 2, '.', ',' )}} <br> @php($total_peso_ruta = $total_peso_ruta + ($det2_ex->dias * $det2_ex->total_ninos * $det2_ex->peso_racion)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det2_ex->dias * $det2_ex->total_ninos * $det2_ex->peso_racion)/453.59237)/100, 2, '.', ',' ) }} <br> @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det2_ex->dias * $det2_ex->total_ninos * $det2_ex->peso_racion)/453.59237)/100))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det2_ex->dias * $det2_ex->total_ninos * $det2_ex->peso_racion)/1000)/50, 2, '.', ',' ) }}
                                    </p>

                                @endif
                            @endforeach

                            
                        @endif
                    </div>
                    <div class="col-md-3">
                        <b style="color:blue;">Lideres</b><br>
                        @foreach($det_escuelas_l_enc as $det_l_enc)
                            @php($id_racion_l = 0)
                            @if($det_l_enc->escuela_id == $det->escuela_id)
                                @php($id_racion_l = $det_l_enc->idracion)
                            @endif
                        @endforeach

                        @if($id_racion_l == 5)
                            @foreach($det_escuelas_l as $det3)
                                @if($det3->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det3->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det3->total_personas }} <br>
                                        <b>Raciones:</b> {{ number_format($det3->dias * $det3->total_personas)}} <br>
                                        <b>Peso de Raciones en Libras:</b> {{ number_format( ($det3->dias * $det3->total_personas * $det3->peso_racion), 2, '.', ',') }} <br> @php($total_peso_ruta = $total_peso_ruta + ( ($det3->dias * $det3->total_personas * $det3->peso_racion)*453.59237)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det3->dias * $det3->total_personas * $det3->peso_racion)/100), 2, '.', ',' ) }} <br>  @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det3->dias * $det3->total_personas * $det3->peso_racion)/100)))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det3->dias * $det3->total_personas * $det3->peso_racion)/110), 2, '.', ',' ) }}
                                    </p> 
                                @endif
                            @endforeach
                        @else
                            <span style="color: red;"> Datos de ración de expansion</span>
                            @foreach($det_escuelas_l_ex as $det3_ex)
                                @if($det3_ex->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det3_ex->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det3_ex->total_personas }} <br>
                                        <b>Raciones:</b> {{ number_format($det3_ex->dias * $det3_ex->total_personas)}} <br>
                                        <b>Peso de Raciones en Libras:</b> {{ number_format( ($det3_ex->dias * $det3_ex->total_personas * $det3_ex->peso_racion), 2, '.', ',') }} <br> @php($total_peso_ruta = $total_peso_ruta + ( ($det3_ex->dias * $det3_ex->total_personas * $det3_ex->peso_racion)*453.59237)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det3_ex->dias * $det3_ex->total_personas * $det3_ex->peso_racion)/100), 2, '.', ',' ) }} <br>  @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det3_ex->dias * $det3_ex->total_personas * $det3_ex->peso_racion)/100)))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det3_ex->dias * $det3_ex->total_personas * $det3_ex->peso_racion)/110), 2, '.', ',' ) }}
                                    </p> 
                                @endif
                            @endforeach                            
                        @endif
                    </div>
                    <div class="col-md-3">          
                        @foreach($det_escuelas_v_d_enc as $det_v_d_enc)
                            @php($id_racion_v_d = 0)
                            @if($det_v_d_enc->escuela_id == $det->escuela_id)
                                @php($id_racion_v_d = $det_v_d_enc->idracion)
                            @endif
                        @endforeach

                        <b style="color:blue;">Voluntarios y Docentes</b><br>
                        @if($id_racion_v_d == 4)
                            @foreach($det_escuelas_v_d as $det4)
                                @if($det4->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det4->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det4->total_personas }} <br>
                                        <b>Raciones:</b> {{ number_format($det4->dias * $det4->total_personas)}} <br>
                                        <b>Peso de Raciones en Libras:</b> {{ number_format( ($det4->dias * $det4->total_personas * $det4->peso_racion), 2, '.', ',' ) }} <br> @php($total_peso_ruta = $total_peso_ruta + ( ($det4->dias * $det4->total_personas * $det4->peso_racion)*453.59237)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det4->dias * $det4->total_personas * $det4->peso_racion)/100), 2, '.', ',' ) }} <br> @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det4->dias * $det4->total_personas * $det4->peso_racion)/100)))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det4->dias * $det4->total_personas * $det4->peso_racion)/110), 2, '.', ',' ) }}
                                    </p> 
                                @endif
                            @endforeach

                        @else
                            <span style="color: red;"> Datos de ración de expansion</span>
                            @foreach($det_escuelas_v_d_ex as $det4_ex)
                                @if($det4_ex->escuela_id == $det->escuela_id)
                                    <p> 
                                        <b>Dias/Mes:</b> {{ $det4_ex->dias }} <br>
                                        <b>No. Beneficiarios:</b> {{ $det4_ex->total_personas }} <br>
                                        <b>Raciones:</b> {{ number_format($det4_ex->dias * $det4_ex->total_personas)}} <br>
                                        <b>Peso de Raciones en Libras:</b> {{ number_format( ($det4_ex->dias * $det4_ex->total_personas * $det4_ex->peso_racion), 2, '.', ',' ) }} <br> @php($total_peso_ruta = $total_peso_ruta + ( ($det4_ex->dias * $det4_ex->total_personas * $det4_ex->peso_racion)*453.59237)  ) 
                                        <b>Peso de Raciones en Quintales:</b> {{ number_format( (($det4_ex->dias * $det4_ex->total_personas * $det4_ex->peso_racion)/100), 2, '.', ',' ) }} <br> @php($total_peso_ruta_quintales = $total_peso_ruta_quintales + ((($det4_ex->dias * $det4_ex->total_personas * $det4_ex->peso_racion)/100)))
                                        <b>Unidades de Racion:</b> {{ number_format( (($det4_ex->dias * $det4_ex->total_personas * $det4_ex->peso_racion)/110), 2, '.', ',' ) }}
                                    </p> 
                                @endif
                            @endforeach
                            
                        @endif
                        
                    </div>
                </div>
                    <p> 
                        <b style="color: red;">Peso Total de Raciones en Gramos:</b> {{ number_format( $total_peso_ruta, 2, '.', ',' ) }} <br> 
                        <b style="color: red;">Peso Total de Raciones en Quintales:</b> {{ number_format( ( ($total_peso_ruta/453.59237)/100), 2, '.', ',' ) }} 
                    </p> 
                <hr>
            @endforeach
                                
        @else
            <b style="color: red;">Ruta sin datos, asigne las escuelas primero ó verifique el detalle de la solicitud.</b>
        @endif

    </div> 

    <div class="card-footer clearfix">
        <b>Total de Raciones de la Ruta: </b> {{number_format($total_raciones)}}
        <b>Total de Quintales de la Ruta: </b>  {{number_format($total_peso_ruta_quintales, 2, '.', ',')}}
    </div>

</div>