<div class="wrap" id="myplugin-admin">
    <div id="icon-tools" class="icon32"><br></div>
    <h2><?php echo $this->get_page_title(); ?></h2>
    <?php settings_errors(); ?>
    <!-- validar si se elimina esta parte, ya que WP la hace automáticamente -->
    <?php if (!empty($_GET['settings-updated'])) : ?>
        <div id="setting-error-settings_updated" class="updated settings-error notice notice-success is-dismissible">
            <p><strong><?php _e('Opciones guardadas.', 'smuebleria_plugin') ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Ya vi este aviso.</span></button>
        </div>
    
    <?php endif; ?>
    <form action="options.php" method="POST">
        <?php settings_fields($this->get_slug()); ?>
        <?php do_settings_sections($this->get_slug()); ?>
        <p> 
            <input type="submit" name="guardar" class="button button-primary" value="Guardar" id="guardar">
            <!--TODO: Quitar botón para cargar productos
            <input type="submit" name="cargar" class="button button-secondary" value="Cargar Productos" id="cargar">-->
        </p>
    </form>
</div>