<?php

/**
 * Enmascara un valor y deja visibles sus últimos 3 caracteres.
 * Un valor de 3 caracteres o menos queda completo como ***.
 *
 * @param string $value Valor a enmascarar.
 * @return string
 */
function ug_mask_erp_value($value){
	if ($value === ''){
		return '';
	}
	return strlen($value) > 3 ? '***' . substr($value, -3) : '***';
}

/**
 * Enmascara las credenciales del ERP y la contraseña del cliente en un texto.
 * Cubre URLs (clave=valor) y arreglos o respuestas pasados por print_r ([clave] => valor).
 * La empresa y la contraseña se ocultan completas.
 *
 * @param string $text Texto que se va a registrar o mostrar.
 * @return string
 */
function ug_mask_erp_data($text){
	$keys = 'claveServicio|idEmpresa|idUsuario|password|smu_clave_servicio|smu_empresa|smu_usuario';
	$mask = function($name, $value){
		$value = trim($value);
		if (in_array(strtolower($name), array('password', 'idempresa', 'smu_empresa'), true)){
			return $value === '' ? '' : '***';
		}
		return ug_mask_erp_value($value);
	};
	$text = preg_replace_callback('/\b(' . $keys . ')=([^&\s"\'<]*)/i', function($m) use ($mask){
		return $m[1] . '=' . $mask($m[1], $m[2]);
	}, (string) $text);
	return preg_replace_callback('/\[(' . $keys . ')(:[^\]]*)?\]\s*=>([^\r\n]*)/i', function($m) use ($mask){
		return '[' . $m[1] . '] => ' . $mask($m[1], $m[3]);
	}, $text);
}

/**
 * Los scripts de carga muestran el detalle completo solo si la URL lleva ?debug=1
 * (o, en línea de comandos, el argumento debug). Sin eso muestran un resumen.
 */
function ug_debug_enabled(){
	if (php_sapi_name() === 'cli'){
		global $argv;
		return isset($argv) && in_array('debug', $argv, true);
	}
	return isset($_GET['debug']) && '1' === $_GET['debug'];
}

function ug_debug_echo($text){
	if (ug_debug_enabled()){
		echo $text;
	}
}
