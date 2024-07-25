<?php
date_default_timezone_set('America/Mexico_City');
include_once  "../../../wp-load.php";
include_once "../../../wp-admin/includes/plugin.php";



// grabar en BD
function    ug_add_product_db($producto){
	global $wpdb;
	global $nl;
	$wpdb->show_errors = true;

	$table_name = $wpdb->prefix . "queue_products"; 
	if (!(is_plugin_active('woocommerce/woocommerce.php')|| is_plugin_active_for_network('woocommerce/woocommerce.php'))){
		echo "Woocommerce no está instalado y activado.";
		return;
	}
	echo "Agregando producto " . print_r($producto, true) . " a BD\n<br>";
	$a = array(
		'idProducto'=> $producto->IdProducto,
		'descontinuado'=> $producto->Descontinuado,
		'existencias'=> $producto->Existensia,
		'nombre'=> $producto->Nombre,
		'descripcion'=> $producto->Descripcion,
		'precioLista'=> $producto->PrecioLista,
		'precio'=> $producto->Precio,
		'peso'=> $producto->Peso,
		'largo'=> $producto->Largo,
		'ancho'=> $producto->Ancho,
		'alto'=> $producto->Alto,
		'ultima_actualizacion'=> date('Y-m-d H:i:s'),
		'procesado' => null,
		//'idRama' => $producto->IdRama,
		'detalle_productos' => $producto->DetalleProductos?json_encode($producto->DetalleProductos):null,
	);

	$s = '';
	$c = count($producto->RutaImagenes);
	for ($i=0;$i<$c;$i++){
		$s .= $producto->RutaImagenes[$i];
		if ($i < $c) $s .= ',';
	}
	error_log ("Producto ($i) agregado en BD "  . $producto->IdProducto .")");
	echo "Producto ($i) agregado en BD "  . $producto->IdProducto .") $nl";
	error_log ('Cadena de imagenes ' . $s) . "\n";
	$a = array_merge($a, array('rutaImagenes'=> $s));
	echo ("registro: " . print_r($a,true)) . "\n";

	$result = $wpdb->update($table_name,$a,array('idProducto'=> $producto->IdProducto));
	echo "query:  (" . print_r($wpdb->last_query, true) . ")$nl";
	echo "Error: " . print_r($wpdb->last_error, true) . "$nl";
	echo "resultado de update:  (" . print_r($result, true) . ")$nl";
	if (($result===false) || ($result === 0)){
		$wpdb->insert($table_name, $a);
	}
	
}



// rutina principal
	global $nl;
	(php_sapi_name() === 'cli')?$nl = "\n":$nl="<br>";
	$smu_config_options = get_option("smu_config_options", false);
	if ($smu_config_options === false){
		$msj = "no se tiene información de acceso al ERP\n<br>";
		error_log($msj);
		echo $msj;
		return;
	}
	$msj =  "Opciones de configuración: " . print_r ($smu_config_options, true) . "\n<br>";
	error_log($msj);
	echo $msj;

	$url = 'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc';
	$url = 'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc/ObtenerDatosProductos2' . '?' .
		'claveServicio=' . $smu_config_options['smu_clave_servicio'] . 
		'&idEmpresa=' . $smu_config_options['smu_empresa'] . 
		'&idUsuario=' .  $smu_config_options['smu_usuario'];
	$msj = __FUNCTION__ . " url para el ERP: " . print_r($url, true) . "\n<br>";
	error_log($msj);
	echo $msj;
	$response = wp_remote_get($url);
	
	if (is_wp_error($response)){
		$msj = "ERROR Respuesta HTTP (" . print_r($response->get_error_codes(), true) . ") Conexion intentada con $url" . "\n<br>";
		error_log($msj);
		echo $msj;
	}else{
		$productos = json_decode($response['body']);
		$n = count($productos);
		$msj = "Recibí $n productos" . "\n<br>";
		error_log($msj);
		echo $msj;
		if ($n==0){
			$msj = "ERROR no hay productos para carga" . "\n<br>";
			error_log($msj);
			echo $msj;
		}else{
			echo "Iniciando carga de productos\n<br>";
			foreach ($productos as $producto){
				ug_add_product_db($producto);
				echo $nl;
			}
			$msj = "Re carga exitosa de productos desde ERP " . "\n<br>";
			error_log($msj);
			echo $msj;
		}
	}
