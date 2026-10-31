<?php
date_default_timezone_set('America/Mexico_City');
include_once  "../../../wp-load.php";
include_once "../../../wp-admin/includes/plugin.php";
include_once "includes/smueblerias-log.php";
include_once "admin/ugdev.php";



// Largo máximo de los textos de la cola; se recortan a este largo antes de guardar
function ug_queue_text_limits(){
	return array('nombre' => 100, 'descripcion' => 6400, 'rutaImagenes' => 12800);
}

// Compara lo que llega del ERP con lo guardado en la cola, con el mismo recorte y redondeo que hace la tabla
function ug_queue_row_changed($actual, $nuevo){
	$texto = ug_queue_text_limits();
	$decimal = array('precio', 'precioLista', 'peso', 'largo', 'ancho', 'alto');
	foreach ($nuevo as $campo => $valor){
		if (isset($texto[$campo])){
			if (mb_substr((string) $valor, 0, $texto[$campo]) !== (string) $actual[$campo]){
				return true;
			}
		} elseif (in_array($campo, $decimal, true)){
			if (round((float) $valor, 2) != round((float) $actual[$campo], 2)){
				return true;
			}
		} elseif ((int) $valor != (int) $actual[$campo]){
			return true;
		}
	}
	return false;
}

// grabar en BD
// Devuelve 'nuevo', 'cambiado', 'sin cambios' o 'error'. Un producto sin cambios conserva su marca de procesado.
function ug_add_product_db($producto){
	global $wpdb;
	global $nl;
	$wpdb->show_errors = true;

	$table_name = $wpdb->prefix . "queue_products";
	$s = '';
	$c = count($producto->RutaImagenes);
	for ($i=0;$i<$c;$i++){
		$s .= $producto->RutaImagenes[$i];
		if ($i < $c) $s .= ',';
	}
	$a = array(
		'idProducto'=> $producto->IdProducto,
		'idCategoria'=> $producto->IdCategoria,
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
		'rutaImagenes'=> $s,
	);
	foreach (ug_queue_text_limits() as $campo => $max){
		if ('descripcion' === $campo && mb_strlen((string) $a[$campo]) > $max){
			error_log("AVISO: la descripción del producto " . $producto->IdProducto . " tiene más de $max caracteres y se recortó");
			echo "AVISO: la descripción del producto " . $producto->IdProducto . " se recortó a $max caracteres.\n<br>";
		}
		$a[$campo] = mb_substr((string) $a[$campo], 0, $max);
	}

	$actual = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE idProducto = %d", $producto->IdProducto), ARRAY_A);
	if ($actual !== null && !ug_queue_row_changed($actual, $a)){
		ug_debug_echo("Producto " . $producto->IdProducto . " sin cambios$nl");
		return 'sin cambios';
	}

	$a['ultima_actualizacion'] = date('Y-m-d H:i:s');
	$a['procesado'] = null;
	error_log ("Producto ($i) agregado en BD "  . $producto->IdProducto .")");
	ug_debug_echo("Producto ($i) agregado en BD "  . $producto->IdProducto .") $nl");
	error_log ('Cadena de imagenes ' . $s) . "\n";
	ug_debug_echo("registro: " . print_r($a,true) . "\n");

	if ($actual === null){
		$result = $wpdb->insert($table_name, $a);
		$estado = 'nuevo';
	}else{
		$result = $wpdb->update($table_name, $a, array('idProducto'=> $producto->IdProducto));
		$estado = 'cambiado';
	}
	if ($result === false){
		error_log("ERROR no pude guardar en la cola el producto " . $producto->IdProducto . ": " . $wpdb->last_error);
		echo "ERROR no pude guardar en la cola el producto " . $producto->IdProducto . ": " . print_r($wpdb->last_error, true) . "$nl";
		return 'error';
	}
	ug_debug_echo(" $estado" . PHP_EOL);
	return $estado;
}


// Oculta y agota los productos de la cola que el ERP ya no mandó, y anota la fecha en el producto (_smu_missing_since).
// Los que regresan se vuelven a procesar para mostrarlos de nuevo.
// Si el ERP mandó menos de la mitad de lo que hay en la cola, se considera una respuesta incompleta y no se oculta nada.
function ug_hide_missing_products($recibidos){
	global $wpdb;
	$table_name = $wpdb->prefix . "queue_products";
	$result = array('ocultos' => 0, 'regresaron' => 0, 'omitido' => false);
	$recibidos = array_map('strval', $recibidos);
	$en_cola = array_map('strval', $wpdb->get_col("SELECT idProducto FROM $table_name"));
	if (count($en_cola) > 0 && count($recibidos) < count($en_cola) / 2){
		error_log("El ERP mandó " . count($recibidos) . " productos y la cola tiene " . count($en_cola) . "; no se oculta ninguno");
		$result['omitido'] = true;
		return $result;
	}

	foreach (array_diff($en_cola, $recibidos) as $id_producto){
		$post_id = ug_find_product_id($id_producto);
		$product = $post_id ? wc_get_product($post_id) : null;
		if (!$product || '' !== (string) $product->get_meta('_smu_missing_since')){
			continue;
		}
		$product->set_catalog_visibility('hidden');
		//Agotado y sin control de existencias, para que no se pueda comprar ni por enlace directo
		$product->set_manage_stock(false);
		$product->set_stock_status('outofstock');
		$product->update_meta_data('_smu_missing_since', current_time('Y-m-d'));
		$product->save();
		$result['ocultos']++;
		error_log("Producto $id_producto ya no viene en el ERP; se ocultó");
	}

	//Los que estaban ocultos y regresaron: se marcan pendientes para que la carga los muestre de nuevo
	$ocultos = $wpdb->get_col("SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->postmeta} m ON m.post_id = pm.post_id AND m.meta_key = '_smu_missing_since' WHERE pm.meta_key = '_IdProducto'");
	foreach (array_intersect(array_map('strval', $ocultos), $recibidos) as $id_producto){
		$wpdb->query($wpdb->prepare("UPDATE $table_name SET procesado = NULL WHERE idProducto = %d", $id_producto));
		$result['regresaron']++;
	}
	return $result;
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
	$msj =  ug_mask_erp_data("Opciones de configuración: " . print_r ($smu_config_options, true)) . "\n<br>";
	error_log($msj);
	ug_debug_echo($msj);

	$url = 'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc';
	$url = 'https://sistema.smuebleria.com/ServicePaginas/SMuebleriaPaginas.svc/ObtenerDatosProductos2' . '?' .
		'claveServicio=' . $smu_config_options['smu_clave_servicio'] . 
		'&idEmpresa=' . $smu_config_options['smu_empresa'] . 
		'&idUsuario=' .  $smu_config_options['smu_usuario'];
	$msj = __FUNCTION__ . " url para el ERP: " . ug_mask_erp_data(print_r($url, true)) . "\n<br>";
	error_log($msj);
	ug_debug_echo($msj);
	$response = wp_remote_get($url);
	
	if (is_wp_error($response)){
		$msj = "ERROR Respuesta HTTP (" . print_r($response->get_error_codes(), true) . ") Conexion intentada con " . ug_mask_erp_data($url) . "\n<br>";
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
			if (!ug_acquire_queue_lock()){
				echo "Ya hay otra carga de la cola en ejecución
<br>";
				return;
			}
			ug_debug_echo("Iniciando carga de productos\n<br>");
			$recibidos = array();
			$resumen = array('nuevo' => 0, 'cambiado' => 0, 'sin cambios' => 0, 'error' => 0);
			foreach ($productos as $producto){
				$recibidos[] = $producto->IdProducto;
				$resumen[ug_add_product_db($producto)]++;
				ug_debug_echo($nl);
			}
			echo "Resumen: {$resumen['nuevo']} nuevos, {$resumen['cambiado']} con cambios, {$resumen['sin cambios']} sin cambios, {$resumen['error']} con error\n<br>";
			$ocultos = ug_hide_missing_products($recibidos);
			if ($ocultos['omitido']){
				echo "AVISO: el ERP mandó menos de la mitad de los productos de la cola; no se ocultó ninguno.\n<br>";
			}else{
				echo "Ocultos por no venir en el ERP: {$ocultos['ocultos']}, de regreso: {$ocultos['regresaron']}\n<br>";
			}
			$msj = "Re carga exitosa de productos desde ERP " . "\n<br>";
			error_log($msj);
			echo $msj;
		}
	}