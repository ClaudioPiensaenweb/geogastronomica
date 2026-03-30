<?php
/**
 * Stats Tracker — Tabla custom y logica de estadisticas.
 *
 * @package GeoGastronomica
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona la tabla custom de estadisticas y el cron de agregacion.
 */
class Stats_Tracker {

	/**
	 * Hook del cron de agregacion.
	 */
	public const CRON_HOOK = 'geo_aggregate_stats';

	/**
	 * Dias de retencion de eventos raw.
	 */
	private const RAW_RETENTION_DAYS = 30;

	/**
	 * Inicializar hooks.
	 */
	public function init(): void {
		add_action( self::CRON_HOOK, array( $this, 'aggregate_and_purge' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_stats_meta_box' ) );
	}

	/**
	 * Obtener nombre de la tabla.
	 *
	 * @return string Nombre completo con prefijo.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'geoad_stats';
	}

	/**
	 * Crear tabla al activar el plugin.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			event_type varchar(20) NOT NULL DEFAULT 'impression',
			event_date date NOT NULL,
			count int(11) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_post_event_date (post_id, event_type, event_date)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Eliminar tabla al desinstalar.
	 */
	public static function drop_table(): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	/**
	 * Registrar un evento (impression o click).
	 *
	 * @param int    $post_id    ID del anuncio.
	 * @param string $event_type Tipo: 'impression' o 'click'.
	 * @return bool True si se registro correctamente.
	 */
	public function record_event( int $post_id, string $event_type ): bool {
		global $wpdb;

		if ( ! in_array( $event_type, array( 'impression', 'click' ), true ) ) {
			return false;
		}

		if ( get_post_type( $post_id ) !== CPT_Anuncio::POST_TYPE ) {
			return false;
		}

		$table = self::table_name();
		$today = current_time( 'Y-m-d' );

		// Upsert: incrementar si ya existe registro para hoy.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE post_id = %d AND event_type = %s AND event_date = %s",
				$post_id,
				$event_type,
				$today
			)
		);

		if ( $existing ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET count = count + 1 WHERE id = %d",
					$existing
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'post_id'    => $post_id,
					'event_type' => $event_type,
					'event_date' => $today,
					'count'      => 1,
				),
				array( '%d', '%s', '%s', '%d' )
			);
		}

		return true;
	}

	/**
	 * Obtener totales de un anuncio (alias de get_period_stats sin filtro de fecha).
	 *
	 * @param int $post_id ID del anuncio.
	 * @return array { impressions: int, clicks: int, ctr: float }
	 */
	public function get_totals( int $post_id ): array {
		return $this->get_period_stats( $post_id, 0 );
	}

	/**
	 * Obtener totales de un anuncio en un periodo.
	 *
	 * @param int $post_id ID del anuncio.
	 * @param int $days    Numero de dias hacia atras (0 = todos).
	 * @return array { impressions: int, clicks: int, ctr: float }
	 */
	public function get_period_stats( int $post_id, int $days = 0 ): array {
		global $wpdb;
		$table = self::table_name();

		if ( $days > 0 ) {
			$cutoff  = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT event_type, SUM(count) as total FROM {$table} WHERE post_id = %d AND event_date >= %s GROUP BY event_type",
					$post_id,
					$cutoff
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT event_type, SUM(count) as total FROM {$table} WHERE post_id = %d GROUP BY event_type",
					$post_id
				)
			);
		}

		$stats = array( 'impressions' => 0, 'clicks' => 0, 'ctr' => 0.0 );
		foreach ( $results as $row ) {
			if ( 'impression' === $row->event_type ) {
				$stats['impressions'] = (int) $row->total;
			} elseif ( 'click' === $row->event_type ) {
				$stats['clicks'] = (int) $row->total;
			}
		}
		if ( $stats['impressions'] > 0 ) {
			$stats['ctr'] = round( ( $stats['clicks'] / $stats['impressions'] ) * 100, 2 );
		}
		return $stats;
	}

	/**
	 * Agregar datos y purgar registros antiguos.
	 */
	public function aggregate_and_purge(): void {
		global $wpdb;
		$table    = self::table_name();
		$cutoff   = gmdate( 'Y-m-d', strtotime( '-' . self::RAW_RETENTION_DAYS . ' days' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE event_date < %s",
				$cutoff
			)
		);
	}

	/**
	 * Programar cron de agregacion.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Desprogramar cron.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Registrar meta box de estadisticas.
	 */
	public function register_stats_meta_box(): void {
		add_meta_box(
			'geo_anuncio_stats',
			esc_html__( 'Estadisticas', 'geogastronomica' ),
			array( $this, 'render_stats_meta_box' ),
			CPT_Anuncio::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Renderizar meta box de estadisticas con estimaciones.
	 *
	 * @param \WP_Post $post Post actual.
	 */
	public function render_stats_meta_box( \WP_Post $post ): void {
		$pid      = $post->ID;
		$totals   = $this->get_period_stats( $pid, 0 );
		$last7    = $this->get_period_stats( $pid, 7 );
		$last30   = $this->get_period_stats( $pid, 30 );
		$prometidas = (int) get_post_meta( $pid, '_geo_impresiones_prometidas', true );

		if ( 0 === $totals['impressions'] && 0 === $totals['clicks'] ) {
			echo '<p style="color:#8c8f94;font-size:12px;">' . esc_html__( 'Sin datos aun. Las estadisticas aparecen cuando el anuncio recibe impresiones.', 'geogastronomica' ) . '</p>';
			return;
		}

		// Ritmo diario (ult. 7d, al menos 1 dia para evitar division por cero).
		$daily_rate = $last7['impressions'] > 0 ? round( $last7['impressions'] / 7 ) : 0;

		// Fecha estimada de cumplimiento.
		$estimated_date = '';
		if ( $prometidas > 0 && $daily_rate > 0 && $totals['impressions'] < $prometidas ) {
			$days_left      = ceil( ( $prometidas - $totals['impressions'] ) / $daily_rate );
			$estimated_date = gmdate( 'd/m/Y', strtotime( "+{$days_left} days" ) );
		}

		// Progreso.
		$progress_pct = 0;
		if ( $prometidas > 0 ) {
			$progress_pct = min( 100, round( ( $totals['impressions'] / $prometidas ) * 100, 1 ) );
		}

		// Color CTR: >= 2% verde, >= 0.5% amarillo, < 0.5% rojo.
		if ( $totals['ctr'] >= 2 ) {
			$ctr_color = '#00a32a';
			$ctr_label = 'Bueno';
		} elseif ( $totals['ctr'] >= 0.5 ) {
			$ctr_color = '#dba617';
			$ctr_label = 'Normal';
		} else {
			$ctr_color = '#d63638';
			$ctr_label = 'Bajo';
		}
		?>
		<div class="geo-stats-box">

			<?php if ( $prometidas > 0 ) : ?>
			<!-- Progreso vs contratadas -->
			<div class="geo-stats-section">
				<div class="geo-stats-label"><?php esc_html_e( 'Progreso del contrato', 'geogastronomica' ); ?></div>
				<div class="geo-stats-progress">
					<div class="geo-stats-bar">
						<div class="geo-stats-bar-fill" style="width:<?php echo esc_attr( $progress_pct ); ?>%;"></div>
					</div>
					<div class="geo-stats-progress-text">
						<span><?php echo esc_html( number_format_i18n( $totals['impressions'] ) ); ?> / <?php echo esc_html( number_format_i18n( $prometidas ) ); ?></span>
						<strong><?php echo esc_html( $progress_pct . '%' ); ?></strong>
					</div>
				</div>
				<?php if ( $totals['impressions'] >= $prometidas ) : ?>
					<div class="geo-stats-badge geo-stats-badge--success"><?php esc_html_e( 'Objetivo cumplido', 'geogastronomica' ); ?></div>
				<?php elseif ( $estimated_date ) : ?>
					<div class="geo-stats-meta"><?php printf( esc_html__( 'Fin estimado: %s (ritmo %s/dia)', 'geogastronomica' ), esc_html( $estimated_date ), esc_html( number_format_i18n( $daily_rate ) ) ); ?></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Totales -->
			<div class="geo-stats-section">
				<div class="geo-stats-label"><?php esc_html_e( 'Total acumulado', 'geogastronomica' ); ?></div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'Impresiones', 'geogastronomica' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $totals['impressions'] ) ); ?></strong>
				</div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'Clicks', 'geogastronomica' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $totals['clicks'] ) ); ?></strong>
				</div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'CTR', 'geogastronomica' ); ?></span>
					<strong style="color:<?php echo esc_attr( $ctr_color ); ?>">
						<?php echo esc_html( $totals['ctr'] . '%' ); ?>
						<span class="geo-stats-ctr-label"><?php echo esc_html( $ctr_label ); ?></span>
					</strong>
				</div>
			</div>

			<!-- Ultimos 30 dias -->
			<div class="geo-stats-section">
				<div class="geo-stats-label"><?php esc_html_e( 'Ultimos 30 dias', 'geogastronomica' ); ?></div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'Impresiones', 'geogastronomica' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $last30['impressions'] ) ); ?></strong>
				</div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'Clicks', 'geogastronomica' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $last30['clicks'] ) ); ?></strong>
				</div>
				<div class="geo-stats-row">
					<span><?php esc_html_e( 'Ritmo diario (7d)', 'geogastronomica' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $daily_rate ) ); ?>/dia</strong>
				</div>
			</div>

		</div>
		<?php
	}
}
