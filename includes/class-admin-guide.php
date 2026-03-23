<?php
/**
 * Pagina de guia para anunciantes y formacion para el editor.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra pagina de guia bajo el menu GeoGastronomica.
 */
class Admin_Guide {

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
	}

	/**
	 * Anadir submenu bajo GeoGastronomica.
	 */
	public function add_submenu(): void {
		add_submenu_page(
			'edit.php?post_type=' . CPT_Anuncio::POST_TYPE,
			esc_html__( 'Guia', 'geogastronomica' ),
			esc_html__( 'Guia', 'geogastronomica' ),
			'edit_posts',
			'geogastronomica-guide',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renderizar pagina de guia.
	 */
	public function render_page(): void {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'anunciantes';
		$packs      = Settings::get_packs();
		?>
		<div class="wrap geo-guide-wrap">
			<h1><?php esc_html_e( 'GeoGastronomica — Guia', 'geogastronomica' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?post_type=<?php echo esc_attr( CPT_Anuncio::POST_TYPE ); ?>&page=geogastronomica-guide&tab=anunciantes"
				   class="nav-tab <?php echo 'anunciantes' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-megaphone" style="margin-right:4px;"></span>
					<?php esc_html_e( 'Guia para anunciantes', 'geogastronomica' ); ?>
				</a>
				<a href="?post_type=<?php echo esc_attr( CPT_Anuncio::POST_TYPE ); ?>&page=geogastronomica-guide&tab=formacion"
				   class="nav-tab <?php echo 'formacion' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-welcome-learn-more" style="margin-right:4px;"></span>
					<?php esc_html_e( 'Formacion para el editor', 'geogastronomica' ); ?>
				</a>
			</nav>

			<div class="geo-guide-content">
				<?php
				if ( 'formacion' === $active_tab ) {
					$this->render_tab_formacion();
				} else {
					$this->render_tab_anunciantes( $packs );
				}
				?>
			</div>
		</div>

		<?php $this->render_styles(); ?>
		<?php
	}

	/**
	 * Tab: Guia para anunciantes.
	 *
	 * @param array $packs Packs configurados.
	 */
	private function render_tab_anunciantes( array $packs ): void {
		$zone_labels = array(
			'vertical_1'   => 'Vertical 1',
			'vertical_2'   => 'Vertical 2',
			'vertical_3'   => 'Vertical 3',
			'horizontal_1' => 'Horizontal 1',
			'horizontal_2' => 'Horizontal 2',
		);
		$page_labels = array(
			'home'         => 'Pagina de inicio',
			'categoria'    => 'Pagina de categoria',
			'subcategoria' => 'Subcategoria / Articulo / Autor',
		);
		?>

		<!-- INTRO -->
		<div class="geo-guide-card">
			<h2><?php esc_html_e( 'Informacion para anunciantes', 'geogastronomica' ); ?></h2>
			<p><?php esc_html_e( 'Esta guia contiene toda la informacion que necesitas compartir con los anunciantes: formatos de imagen, tamanos requeridos y packs disponibles.', 'geogastronomica' ); ?></p>
			<p><strong><?php esc_html_e( 'Puedes copiar y enviar esta informacion directamente al anunciante.', 'geogastronomica' ); ?></strong></p>
		</div>

		<!-- FORMATOS DE IMAGEN -->
		<div class="geo-guide-card">
			<h2><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Formatos y tamanos de imagen', 'geogastronomica' ); ?></h2>
			<p><?php esc_html_e( 'El anunciante debe proporcionar las imagenes en los siguientes tamanos segun las zonas contratadas:', 'geogastronomica' ); ?></p>

			<table class="geo-guide-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Formato', 'geogastronomica' ); ?></th>
						<th><?php esc_html_e( 'Tamano (px)', 'geogastronomica' ); ?></th>
						<th><?php esc_html_e( 'Proporcion', 'geogastronomica' ); ?></th>
						<th><?php esc_html_e( 'Uso', 'geogastronomica' ); ?></th>
						<th><?php esc_html_e( 'Obligatorio', 'geogastronomica' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<span class="geo-format-badge geo-format-vertical">Vertical</span>
						</td>
						<td><code>285 x 627 px</code></td>
						<td>~1:2.2</td>
						<td><?php esc_html_e( 'Sidebar lateral en desktop', 'geogastronomica' ); ?></td>
						<td><?php esc_html_e( 'Si el pack incluye zonas verticales', 'geogastronomica' ); ?></td>
					</tr>
					<tr>
						<td>
							<span class="geo-format-badge geo-format-horizontal">Horizontal</span>
						</td>
						<td><code>1230 x 350 px</code></td>
						<td>~3.5:1</td>
						<td><?php esc_html_e( 'Banner ancho en desktop', 'geogastronomica' ); ?></td>
						<td><?php esc_html_e( 'Si el pack incluye zonas horizontales', 'geogastronomica' ); ?></td>
					</tr>
					<tr>
						<td>
							<span class="geo-format-badge geo-format-movil">Movil</span>
						</td>
						<td><code>1000 x 400 px</code></td>
						<td>5:2</td>
						<td><?php esc_html_e( 'Todos los banners en movil', 'geogastronomica' ); ?></td>
						<td><strong><?php esc_html_e( 'Siempre recomendado', 'geogastronomica' ); ?></strong></td>
					</tr>
				</tbody>
			</table>

			<div class="geo-guide-tips">
				<h3><?php esc_html_e( 'Requisitos tecnicos', 'geogastronomica' ); ?></h3>
				<ul>
					<li><strong><?php esc_html_e( 'Formatos aceptados:', 'geogastronomica' ); ?></strong> JPG, PNG, WebP</li>
					<li><strong><?php esc_html_e( 'Peso maximo recomendado:', 'geogastronomica' ); ?></strong> <?php esc_html_e( '200 KB por imagen (para carga rapida)', 'geogastronomica' ); ?></li>
					<li><strong><?php esc_html_e( 'Resolucion:', 'geogastronomica' ); ?></strong> <?php esc_html_e( '72 DPI (pantalla)', 'geogastronomica' ); ?></li>
					<li><strong><?php esc_html_e( 'Imagen movil:', 'geogastronomica' ); ?></strong> <?php esc_html_e( 'Si no se proporciona, se usara la imagen principal recortada. Para mejor resultado, proporcionar siempre.', 'geogastronomica' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- PACKS DISPONIBLES -->
		<div class="geo-guide-card">
			<h2><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Packs disponibles', 'geogastronomica' ); ?></h2>
			<p><?php esc_html_e( 'Estos son los packs de visibilidad que puedes ofrecer a los anunciantes:', 'geogastronomica' ); ?></p>

			<div class="geo-guide-packs">
				<?php foreach ( $packs as $slug => $pack ) : ?>
					<div class="geo-guide-pack-card">
						<div class="geo-guide-pack-header">
							<h3><?php echo esc_html( $pack['name'] ); ?></h3>
							<?php if ( ! empty( $pack['price'] ) ) : ?>
								<span class="geo-guide-pack-price"><?php echo esc_html( $pack['price'] ); ?>&euro;</span>
							<?php endif; ?>
						</div>
						<div class="geo-guide-pack-body">
							<table class="geo-guide-pack-zones">
								<?php foreach ( $pack['zones'] as $page => $page_zones ) : ?>
									<tr>
										<td class="geo-guide-pack-page"><?php echo esc_html( $page_labels[ $page ] ?? $page ); ?></td>
										<td>
											<?php if ( ! empty( $page_zones ) ) : ?>
												<?php foreach ( $page_zones as $z ) : ?>
													<span class="geo-zone-tag <?php echo str_starts_with( $z, 'vertical' ) ? 'geo-zone-tag-v' : 'geo-zone-tag-h'; ?>">
														<?php echo esc_html( $zone_labels[ $z ] ?? $z ); ?>
													</span>
												<?php endforeach; ?>
											<?php else : ?>
												<span class="geo-zone-tag-none">&mdash;</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</table>

							<?php
							// Calcular imagenes necesarias.
							$needs_vertical   = false;
							$needs_horizontal = false;
							foreach ( $pack['zones'] as $page_zones ) {
								foreach ( $page_zones as $z ) {
									if ( str_starts_with( $z, 'vertical' ) ) {
										$needs_vertical = true;
									}
									if ( str_starts_with( $z, 'horizontal' ) ) {
										$needs_horizontal = true;
									}
								}
							}
							?>
							<div class="geo-guide-pack-images">
								<strong><?php esc_html_e( 'Imagenes necesarias:', 'geogastronomica' ); ?></strong>
								<ul>
									<?php if ( $needs_vertical ) : ?>
										<li>Vertical: <code>285 x 627 px</code></li>
									<?php endif; ?>
									<?php if ( $needs_horizontal ) : ?>
										<li>Horizontal: <code>1230 x 350 px</code></li>
									<?php endif; ?>
									<li>Movil: <code>1000 x 400 px</code> <em>(recomendado)</em></li>
								</ul>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- TEXTO COPIABLE PARA EMAIL -->
		<div class="geo-guide-card">
			<h2><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Plantilla para enviar al anunciante', 'geogastronomica' ); ?></h2>
			<p><?php esc_html_e( 'Copia y pega este texto en un email para enviar las especificaciones al anunciante:', 'geogastronomica' ); ?></p>
			<div class="geo-guide-copybox">
				<button type="button" class="button geo-copy-btn" data-target="geo-email-template">
					<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar al portapapeles', 'geogastronomica' ); ?>
				</button>
				<pre id="geo-email-template"><?php echo esc_html( $this->get_email_template( $packs ) ); ?></pre>
			</div>
		</div>

		<script>
		document.querySelectorAll('.geo-copy-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var target = document.getElementById(this.dataset.target);
				navigator.clipboard.writeText(target.textContent).then(function() {
					btn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copiado';
					setTimeout(function() {
						btn.innerHTML = '<span class="dashicons dashicons-clipboard"></span> Copiar al portapapeles';
					}, 2000);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Generar plantilla de email para anunciantes.
	 *
	 * @param array $packs Packs configurados.
	 * @return string Texto del email.
	 */
	private function get_email_template( array $packs ): string {
		$site_name = get_bloginfo( 'name' );

		$text  = "ESPECIFICACIONES DE PUBLICIDAD - {$site_name}\n";
		$text .= str_repeat( '=', 50 ) . "\n\n";

		$text .= "FORMATOS DE IMAGEN REQUERIDOS\n";
		$text .= str_repeat( '-', 35 ) . "\n\n";

		$text .= "1. Banner Vertical (sidebar lateral):\n";
		$text .= "   Tamano: 285 x 627 pixeles\n";
		$text .= "   Formato: JPG, PNG o WebP\n\n";

		$text .= "2. Banner Horizontal (banner ancho):\n";
		$text .= "   Tamano: 1230 x 350 pixeles\n";
		$text .= "   Formato: JPG, PNG o WebP\n\n";

		$text .= "3. Banner Movil (obligatorio para todos):\n";
		$text .= "   Tamano: 1000 x 400 pixeles\n";
		$text .= "   Formato: JPG, PNG o WebP\n\n";

		$text .= "Peso maximo recomendado: 200 KB por imagen\n";
		$text .= "Resolucion: 72 DPI\n\n";

		$text .= "PACKS DISPONIBLES\n";
		$text .= str_repeat( '-', 35 ) . "\n\n";

		$page_labels = array(
			'home'         => 'Inicio',
			'categoria'    => 'Categoria',
			'subcategoria' => 'Subcategoria/Articulo',
		);

		foreach ( $packs as $slug => $pack ) {
			$price = ! empty( $pack['price'] ) ? " - {$pack['price']}EUR" : '';
			$text .= ">> {$pack['name']}{$price}\n";
			foreach ( $pack['zones'] as $page => $page_zones ) {
				$label = $page_labels[ $page ] ?? $page;
				if ( ! empty( $page_zones ) ) {
					$text .= "   {$label}: " . implode( ', ', $page_zones ) . "\n";
				}
			}
			$text .= "\n";
		}

		$text .= str_repeat( '-', 35 ) . "\n";
		$text .= "Por favor, envie las imagenes en los tamanos indicados.\n";
		$text .= "Si tiene dudas, no dude en contactarnos.\n";

		return $text;
	}

	/**
	 * Tab: Formacion para el editor.
	 */
	private function render_tab_formacion(): void {
		?>
		<div class="geo-guide-card">
			<h2><?php esc_html_e( 'Manual del editor — Gestion de anuncios', 'geogastronomica' ); ?></h2>
			<p><?php esc_html_e( 'Guia paso a paso para gestionar los anuncios del sitio web.', 'geogastronomica' ); ?></p>
		</div>

		<!-- PASO 1: CREAR ANUNCIO -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">1</span>
				<h2><?php esc_html_e( 'Crear un nuevo anuncio', 'geogastronomica' ); ?></h2>
			</div>
			<ol class="geo-guide-instructions">
				<li>
					<?php
					printf(
						esc_html__( 'En el menu lateral, ve a %s.', 'geogastronomica' ),
						'<strong>Anuncios &rarr; Anadir nuevo</strong>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'El titulo se genera automaticamente a partir del nombre de la empresa y la descripcion. No necesitas escribirlo.', 'geogastronomica' ); ?></li>
			</ol>
		</div>

		<!-- PASO 2: INFO EMPRESA -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">2</span>
				<h2><?php esc_html_e( 'Pestana "Info empresa"', 'geogastronomica' ); ?></h2>
			</div>
			<ol class="geo-guide-instructions">
				<li><?php esc_html_e( 'Rellena el nombre de la empresa del anunciante.', 'geogastronomica' ); ?></li>
				<li><?php esc_html_e( 'Anade el email y telefono de contacto (opcionales, son para tu referencia interna).', 'geogastronomica' ); ?></li>
			</ol>
			<div class="geo-guide-note">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Esta informacion NO se muestra en la web. Es solo para tu gestion interna.', 'geogastronomica' ); ?>
			</div>
		</div>

		<!-- PASO 3: SUBIR IMAGENES -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">3</span>
				<h2><?php esc_html_e( 'Pestana "Anuncio" — Subir imagenes', 'geogastronomica' ); ?></h2>
			</div>
			<ol class="geo-guide-instructions">
				<li><?php esc_html_e( 'Escribe una breve descripcion del anuncio (se usa como texto alternativo para SEO).', 'geogastronomica' ); ?></li>
				<li><?php esc_html_e( 'Pega el enlace de destino (la URL a la que lleva el clic en el banner).', 'geogastronomica' ); ?></li>
				<li>
					<?php esc_html_e( 'Sube las imagenes que te ha proporcionado el anunciante:', 'geogastronomica' ); ?>
					<ul>
						<li><strong>Vertical</strong> (285x627) — <?php esc_html_e( 'para sidebars laterales', 'geogastronomica' ); ?></li>
						<li><strong>Horizontal</strong> (1230x350) — <?php esc_html_e( 'para banners anchos', 'geogastronomica' ); ?></li>
						<li><strong>Movil</strong> (1000x400) — <?php esc_html_e( 'para telefonos moviles', 'geogastronomica' ); ?></li>
					</ul>
				</li>
			</ol>
			<div class="geo-guide-note geo-guide-note-warning">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Solo necesitas subir las imagenes de los formatos que incluye el pack contratado. La imagen movil es siempre recomendable.', 'geogastronomica' ); ?>
			</div>
			<div class="geo-guide-note">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Para subir una imagen: haz clic en "Seleccionar", elige la imagen de la biblioteca o subela, y pulsa "Usar esta imagen".', 'geogastronomica' ); ?>
			</div>
		</div>

		<!-- PASO 4: CONFIGURACION -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">4</span>
				<h2><?php esc_html_e( 'Pestana "Configuracion" — Fechas y visibilidad', 'geogastronomica' ); ?></h2>
			</div>
			<ol class="geo-guide-instructions">
				<li>
					<strong><?php esc_html_e( 'Fechas:', 'geogastronomica' ); ?></strong>
					<?php esc_html_e( 'Establece la fecha de inicio y fin de la campana. Si los dejas vacios, el anuncio sera permanente.', 'geogastronomica' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Pack de visibilidad:', 'geogastronomica' ); ?></strong>
					<?php esc_html_e( 'Selecciona el pack que ha contratado el anunciante. Esto determina en que paginas y zonas aparecera su anuncio.', 'geogastronomica' ); ?>
				</li>
			</ol>
			<div class="geo-guide-note">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Si necesitas una configuracion especial que no encaja en ningun pack, selecciona "Personalizado" y marca las zonas manualmente.', 'geogastronomica' ); ?>
			</div>
		</div>

		<!-- PASO 5: PUBLICAR -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">5</span>
				<h2><?php esc_html_e( 'Publicar el anuncio', 'geogastronomica' ); ?></h2>
			</div>
			<ol class="geo-guide-instructions">
				<li><?php esc_html_e( 'Revisa que todo esta correcto.', 'geogastronomica' ); ?></li>
				<li>
					<?php
					printf(
						esc_html__( 'Pulsa el boton %s en la caja de publicacion (arriba a la derecha).', 'geogastronomica' ),
						'<strong>Publicar</strong>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'El anuncio aparecera inmediatamente en las zonas configuradas (o cuando llegue la fecha de inicio si la estableciste).', 'geogastronomica' ); ?></li>
			</ol>
		</div>

		<!-- GESTIONAR ANUNCIOS -->
		<div class="geo-guide-card">
			<div class="geo-guide-step">
				<span class="geo-guide-step-number">+</span>
				<h2><?php esc_html_e( 'Gestionar anuncios existentes', 'geogastronomica' ); ?></h2>
			</div>

			<h3><?php esc_html_e( 'Pausar un anuncio temporalmente', 'geogastronomica' ); ?></h3>
			<p><?php esc_html_e( 'Cambia el estado del anuncio de "Publicado" a "Borrador". El anuncio dejara de mostrarse pero no se elimina.', 'geogastronomica' ); ?></p>

			<h3><?php esc_html_e( 'Cambiar el orden de los anuncios', 'geogastronomica' ); ?></h3>
			<p>
				<?php
				printf(
					esc_html__( 'Ve a %s para reorganizar los anuncios arrastrando y soltando. El orden determina cual se muestra primero cuando hay varios anuncios en la misma zona.', 'geogastronomica' ),
					'<strong>Anuncios &rarr; Ordenar</strong>'
				);
				?>
			</p>

			<h3><?php esc_html_e( 'Eliminar un anuncio', 'geogastronomica' ); ?></h3>
			<p><?php esc_html_e( 'Desde la lista de anuncios, pasa el raton sobre el anuncio y haz clic en "Papelera". Esto lo envia a la papelera (puedes recuperarlo durante 30 dias).', 'geogastronomica' ); ?></p>

			<h3><?php esc_html_e( 'Modificar un anuncio activo', 'geogastronomica' ); ?></h3>
			<p><?php esc_html_e( 'Puedes editar cualquier anuncio publicado en cualquier momento: cambiar imagenes, fechas, pack o enlace. Los cambios se aplican inmediatamente al guardar.', 'geogastronomica' ); ?></p>
		</div>

		<!-- FAQ -->
		<div class="geo-guide-card">
			<h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Preguntas frecuentes', 'geogastronomica' ); ?></h2>

			<div class="geo-guide-faq">
				<h3><?php esc_html_e( 'No veo el anuncio en la web despues de publicarlo', 'geogastronomica' ); ?></h3>
				<p><?php esc_html_e( 'Comprueba: (1) que el anuncio esta "Publicado", (2) que las fechas son correctas, (3) que tiene un pack o zonas asignadas, y (4) que se ha subido al menos una imagen del formato correspondiente.', 'geogastronomica' ); ?></p>

				<h3><?php esc_html_e( 'El anunciante me ha enviado las imagenes en tamano incorrecto', 'geogastronomica' ); ?></h3>
				<p><?php esc_html_e( 'Puedes enviarle la plantilla de especificaciones desde la pestana "Guia para anunciantes" (boton "Copiar al portapapeles"). Si la imagen es parecida al tamano, WordPress la recortara automaticamente, pero el resultado puede no ser optimo.', 'geogastronomica' ); ?></p>

				<h3><?php esc_html_e( 'Quiero que un anuncio aparezca en zonas de packs diferentes', 'geogastronomica' ); ?></h3>
				<p><?php esc_html_e( 'Selecciona el pack "Personalizado" en la configuracion y marca manualmente las zonas que necesites.', 'geogastronomica' ); ?></p>

				<h3><?php esc_html_e( 'Hay varios anuncios en la misma zona, como se muestran?', 'geogastronomica' ); ?></h3>
				<p><?php esc_html_e( 'Cuando hay mas de un anuncio activo en la misma zona, el sistema los rota automaticamente cada pocos segundos con una transicion suave.', 'geogastronomica' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Estilos CSS de la pagina de guia.
	 */
	private function render_styles(): void {
		?>
		<style>
		.geo-guide-wrap { max-width: 100%; }
		.geo-guide-content { margin-top: 20px; }

		.geo-guide-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 6px;
			padding: 24px 28px;
			margin-bottom: 16px;
		}
		.geo-guide-card h2 {
			margin-top: 0;
			font-size: 18px;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.geo-guide-card h3 { font-size: 14px; margin: 16px 0 6px; }

		/* Tabla de formatos */
		.geo-guide-table {
			width: 100%;
			border-collapse: collapse;
			margin: 16px 0;
		}
		.geo-guide-table th,
		.geo-guide-table td {
			padding: 10px 12px;
			text-align: left;
			border-bottom: 1px solid #e5e5e5;
			font-size: 13px;
		}
		.geo-guide-table th {
			background: #f9f9f9;
			font-weight: 600;
			font-size: 12px;
			text-transform: uppercase;
			color: #50575e;
		}
		.geo-guide-table code {
			background: #f0f0f1;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 13px;
			font-weight: 600;
		}

		/* Badges de formato */
		.geo-format-badge {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 4px;
			font-size: 12px;
			font-weight: 600;
			color: #fff;
		}
		.geo-format-vertical { background: #2271b1; }
		.geo-format-horizontal { background: #00a32a; }
		.geo-format-movil { background: #d63638; }

		/* Tips */
		.geo-guide-tips { background: #f0f6fc; border-radius: 4px; padding: 16px; margin-top: 16px; }
		.geo-guide-tips h3 { margin-top: 0; }
		.geo-guide-tips ul { margin: 8px 0 0 16px; }
		.geo-guide-tips li { margin-bottom: 6px; font-size: 13px; }

		/* Packs */
		.geo-guide-packs {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
			gap: 16px;
			margin-top: 16px;
		}
		.geo-guide-pack-card {
			border: 1px solid #e5e5e5;
			border-radius: 6px;
			overflow: hidden;
		}
		.geo-guide-pack-header {
			background: #2271b1;
			color: #fff;
			padding: 14px 16px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.geo-guide-pack-header h3 { margin: 0; color: #fff; font-size: 16px; }
		.geo-guide-pack-price {
			background: rgba(255,255,255,0.2);
			padding: 4px 12px;
			border-radius: 20px;
			font-size: 16px;
			font-weight: 700;
		}
		.geo-guide-pack-body { padding: 16px; }
		.geo-guide-pack-zones { width: 100%; border-collapse: collapse; }
		.geo-guide-pack-zones td { padding: 6px 0; font-size: 13px; border-bottom: 1px solid #f0f0f1; vertical-align: top; }
		.geo-guide-pack-page { font-weight: 600; color: #50575e; white-space: nowrap; padding-right: 12px !important; }

		.geo-zone-tag {
			display: inline-block;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			margin: 1px 2px;
		}
		.geo-zone-tag-v { background: #e7f0f8; color: #2271b1; }
		.geo-zone-tag-h { background: #edf7ee; color: #00a32a; }
		.geo-zone-tag-none { color: #999; }

		.geo-guide-pack-images { margin-top: 12px; font-size: 13px; }
		.geo-guide-pack-images ul { margin: 6px 0 0 16px; }
		.geo-guide-pack-images li { margin-bottom: 4px; }

		/* Copybox */
		.geo-guide-copybox { position: relative; margin-top: 12px; }
		.geo-guide-copybox pre {
			background: #f6f7f7;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 16px;
			font-size: 12px;
			line-height: 1.6;
			white-space: pre-wrap;
			margin-top: 10px;
			max-height: 400px;
			overflow-y: auto;
		}
		.geo-copy-btn .dashicons { font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom; }

		/* Steps */
		.geo-guide-step { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
		.geo-guide-step-number {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 36px;
			height: 36px;
			background: #2271b1;
			color: #fff;
			border-radius: 50%;
			font-size: 18px;
			font-weight: 700;
			flex-shrink: 0;
		}
		.geo-guide-step h2 { margin-bottom: 0; }

		/* Instructions */
		.geo-guide-instructions {
			margin: 12px 0 12px 24px;
			font-size: 14px;
			line-height: 1.7;
		}
		.geo-guide-instructions li { margin-bottom: 8px; }
		.geo-guide-instructions ul { margin: 6px 0 0 18px; list-style: disc; }

		/* Notes */
		.geo-guide-note {
			display: flex;
			align-items: flex-start;
			gap: 8px;
			background: #f0f6fc;
			border-left: 4px solid #2271b1;
			border-radius: 0 4px 4px 0;
			padding: 12px 16px;
			margin: 12px 0;
			font-size: 13px;
		}
		.geo-guide-note .dashicons { color: #2271b1; flex-shrink: 0; margin-top: 1px; }
		.geo-guide-note-warning { background: #fcf9e8; border-left-color: #dba617; }
		.geo-guide-note-warning .dashicons { color: #dba617; }

		/* FAQ */
		.geo-guide-faq h3 { color: #1d2327; margin-top: 20px; }
		.geo-guide-faq p { margin: 4px 0 0; font-size: 13px; color: #50575e; }
		</style>
		<?php
	}
}
