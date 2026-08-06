<?php
	function contenidosplit($contenido){
$html_limpio = strip_tags($contenido);#Limpieza de HTML
$old_string = preg_replace("/[\r\n]/","</p><p>",$html_limpio);#Remplazo de espacios por Parrafos
$new_string = "<p>" . $old_string . "</p>";#sigue limpieza

$html = str_get_html($new_string);
$nota="";


foreach($html->find('p') as $article) {
if($article->tag == 'p') {
        if($article->innertext!=""){
                $nota.="";
                $nota.='<modulo><content>CDATABEGIN'.$article->innertext.'CDATACLOSE</content><type>paragraph</type></modulo>
';
        }
}
}

		return $nota;
	}
