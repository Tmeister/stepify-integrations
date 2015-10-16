<?php
	/**
	 * Providers Setting Builders
	 */
?>

	<div class="wrap">
		<?php screen_icon(); ?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ($this->providers as $tab => $name): ?>
				<?php $class = ( $tab == $current_tab ) ? ' nav-tab-active' : ''; ?>
        		<a class='nav-tab<?php echo $class ?>' href='?page=stepify&amp;tab=<?php echo $tab ?>'><?php echo $name ?></a>
			<?php endforeach ?>
		</h2>
		<div id="tab_container">
			<form method="post" action="options.php">
				<table class="form-table">
				<?php
					settings_fields( 'stepify_settings' );
					do_settings_fields( 'stepify_settings_' . $current_tab, 'stepify_settings_' . $current_tab );
				?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->