
<?php
date_default_timezone_set('America/Mexico_City');
//este archivo irá en el directorio raiz de WP o se debe cambiar la ruta:
include_once "../../../wp-load.php";
include_once "../../../wp-admin/includes/plugin.php";
include_once "admin/ugdev.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $wpdb;
//$table_name = $wpdb->prefix . "queue_products"; 
$sql = "SELECT * FROM {$wpdb->prefix}queue_products where procesado is null";
$a = $wpdb->get_results( $sql );
if (is_null($a)){
	echo "no hay registros para actualiziar\n";
	exit;
}
$c = count($a);

echo "Número de registros leidos: $c\n";
$i=0;
$producto = new stdClass();
foreach ($a as $p) {
	$producto->IdProducto = $p->idProducto;
	$producto->Descripcion = $p->descripcion;
	$producto->Nombre = $p->nombre;
	$producto->Existensia = $p->existencias;
	$producto->Descontinuado = $p->descontinuado;
	$producto->PrecioLista = $p->precioLista;
	$producto->Precio = $p->precio;
	$producto->Peso = $p->peso;
	$producto->Largo = $p->largo;
	$producto->Ancho = $p->ancho;
	$producto->Alto = $p->alto;
	$producto->RutaImagenes = explode(',', trim($p->rutaImagenes, ','));
	//$producto->IdRama = $p->idRama;
	/*if (is_null($p->detalleProductos)) {
		$producto->DetalleProductos = null;
	}else {
		$producto->DetalleProductos = json_decode( $p->detalleProductos, true );
	}*/
	ug_create_product ($producto);
	echo "($i) $p->idProducto\n";
	$i++;
	$sql = "update {$wpdb->prefix}queue_products set procesado = 1 where id={$p->id}";
	echo "Actualizando: $sql\n";
	$a = $wpdb->get_results( $sql );
	if (is_null($a)){
		echo "no pude actualizar el registro {$p->id}\n";
	}
}

/**
 * fin de rutina principal
 */