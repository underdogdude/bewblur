<?php
/**
 * Synchronize portfolio content with YouTube video descriptions.
 *
 * @package bewblur
 */

/**
 * Register the API key field immediately, including before Local JSON is synced.
 */
function bewblur_register_youtube_api_key_field() {
	if ( ! function_exists( 'acf_add_local_field' ) || acf_get_field( 'field_bewblur_youtube_api_key' ) ) {
		return;
	}

	acf_add_local_field(
		array(
			'key'          => 'field_bewblur_youtube_api_key',
			'label'        => 'YouTube Data API Key',
			'name'         => 'youtube_api_key',
			'type'         => 'password',
			'instructions' => 'Required to copy YouTube video descriptions into Portfolio content. Restrict this key to YouTube Data API v3.',
			'maxlength'    => 128,
			'parent'       => 'group_6a51fa3f1d764',
		)
	);
}
add_action( 'acf/init', 'bewblur_register_youtube_api_key_field', 20 );

/**
 * Reject incomplete values before they can replace the working API key.
 *
 * @param bool|string $valid Current validation result.
 * @param mixed       $value Submitted field value.
 * @return bool|string
 */
function bewblur_validate_youtube_api_key( $valid, $value ) {
	if ( true !== $valid || '' === trim( (string) $value ) ) {
		return $valid;
	}

	if ( ! preg_match( '/^AIza[0-9A-Za-z_-]{30,}$/', trim( (string) $value ) ) ) {
		return __( 'Enter the complete Google API key. It should begin with AIza.', 'bewblur' );
	}

	return $valid;
}
add_filter( 'acf/validate_value/key=field_bewblur_youtube_api_key', 'bewblur_validate_youtube_api_key', 10, 2 );

/**
 * Remove accidental whitespace when the API key is saved.
 *
 * @param mixed $value Submitted API key.
 * @return string
 */
function bewblur_normalize_youtube_api_key( $value ) {
	return trim( (string) $value );
}
add_filter( 'acf/update_value/key=field_bewblur_youtube_api_key', 'bewblur_normalize_youtube_api_key' );

/**
 * Prevent password managers from replacing the API key with a login password.
 */
function bewblur_protect_youtube_api_key_input() {
	?>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
		var input = document.querySelector('.acf-field[data-key="field_bewblur_youtube_api_key"] input');
		if (input) {
			input.setAttribute("autocomplete", "new-password");
		}
	});
	</script>
	<?php
}
add_action( 'acf/input/admin_footer', 'bewblur_protect_youtube_api_key_input' );

/**
 * Get the YouTube Data API key without exposing it in theme source.
 *
 * A wp-config.php constant takes precedence over the Theme Settings field.
 *
 * @return string
 */
function bewblur_get_youtube_api_key() {
	if ( defined( 'BEWBLUR_YOUTUBE_API_KEY' ) && BEWBLUR_YOUTUBE_API_KEY ) {
		return trim( (string) BEWBLUR_YOUTUBE_API_KEY );
	}

	if ( function_exists( 'get_field' ) ) {
		return trim( (string) get_field( 'youtube_api_key', 'option' ) );
	}

	return '';
}

/**
 * Fetch descriptions for up to 50 YouTube video IDs in one API request.
 *
 * @param string[] $video_ids YouTube video IDs.
 * @return array|WP_Error Map of video ID to description, or an error.
 */
function bewblur_fetch_youtube_descriptions( $video_ids ) {
	$api_key   = bewblur_get_youtube_api_key();
	$video_ids = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) $video_ids ) ) ) );

	if ( '' === $api_key ) {
		return new WP_Error( 'bewblur_youtube_api_key_missing', __( 'Add a YouTube Data API key in Theme Settings before syncing.', 'bewblur' ) );
	}

	if ( empty( $video_ids ) ) {
		return array();
	}

	$request_url = add_query_arg(
		array(
			'part' => 'snippet',
			'id'   => implode( ',', array_slice( $video_ids, 0, 50 ) ),
			'key'  => $api_key,
		),
		'https://www.googleapis.com/youtube/v3/videos'
	);
	$response = wp_remote_get(
		$request_url,
		array(
			'timeout'     => 20,
			'redirection' => 3,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code = (int) wp_remote_retrieve_response_code( $response );
	$payload     = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $status_code || ! is_array( $payload ) ) {
		$message = isset( $payload['error']['message'] ) ? $payload['error']['message'] : __( 'YouTube returned an invalid response.', 'bewblur' );
		return new WP_Error( 'bewblur_youtube_api_error', sanitize_text_field( $message ) );
	}

	$descriptions = array();
	foreach ( isset( $payload['items'] ) ? $payload['items'] : array() as $item ) {
		if ( empty( $item['id'] ) || ! isset( $item['snippet']['description'] ) ) {
			continue;
		}

		$descriptions[ sanitize_text_field( $item['id'] ) ] = sanitize_textarea_field( $item['snippet']['description'] );
	}

	return $descriptions;
}

/**
 * Decide whether synchronized content may replace the current post content.
 *
 * @param int  $post_id   Portfolio post ID.
 * @param bool $overwrite Whether the administrator explicitly allowed replacement.
 * @return bool
 */
function bewblur_can_update_youtube_description( $post_id, $overwrite = false ) {
	if ( $overwrite ) {
		return true;
	}

	$current_content = (string) get_post_field( 'post_content', $post_id );
	$last_synced     = (string) get_post_meta( $post_id, '_bewblur_youtube_description', true );

	return '' === trim( wp_strip_all_tags( $current_content ) ) || ( '' !== $last_synced && $current_content === $last_synced );
}

/**
 * Save a YouTube description into a portfolio post.
 *
 * @param int    $post_id    Portfolio post ID.
 * @param string $description YouTube description.
 * @param bool   $overwrite  Whether to replace manually edited content.
 * @return bool|WP_Error True when updated, false when intentionally skipped.
 */
function bewblur_update_portfolio_youtube_description( $post_id, $description, $overwrite = false ) {
	$description = sanitize_textarea_field( $description );
	if ( '' === trim( $description ) || ! bewblur_can_update_youtube_description( $post_id, $overwrite ) ) {
		return false;
	}

	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $description,
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	update_post_meta( $post_id, '_bewblur_youtube_description', $description );
	return true;
}

/**
 * Synchronize all portfolio descriptions in two batched requests for this site.
 *
 * @param bool $overwrite Whether to replace manually edited content.
 * @return array|WP_Error Sync totals, or an error.
 */
function bewblur_sync_all_youtube_descriptions( $overwrite = false ) {
	$post_ids = get_posts(
		array(
			'post_type'      => 'portfolio',
			'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$video_posts = array();

	foreach ( $post_ids as $post_id ) {
		$youtube_url = function_exists( 'get_field' ) ? get_field( 'youtube_link', $post_id ) : '';
		$video_id    = function_exists( 'bewblur_get_youtube_id' ) ? bewblur_get_youtube_id( $youtube_url ) : '';

		if ( $video_id ) {
			$video_posts[ $video_id ][] = (int) $post_id;
		}
	}

	$descriptions = array();
	foreach ( array_chunk( array_keys( $video_posts ), 50 ) as $video_id_chunk ) {
		$chunk_descriptions = bewblur_fetch_youtube_descriptions( $video_id_chunk );
		if ( is_wp_error( $chunk_descriptions ) ) {
			return $chunk_descriptions;
		}
		$descriptions += $chunk_descriptions;
	}

	$totals = array(
		'updated' => 0,
		'skipped' => 0,
		'missing' => 0,
		'errors'  => 0,
	);

	foreach ( $video_posts as $video_id => $matching_post_ids ) {
		if ( ! isset( $descriptions[ $video_id ] ) || '' === trim( $descriptions[ $video_id ] ) ) {
			$totals['missing'] += count( $matching_post_ids );
			continue;
		}

		foreach ( $matching_post_ids as $post_id ) {
			$result = bewblur_update_portfolio_youtube_description( $post_id, $descriptions[ $video_id ], $overwrite );
			if ( is_wp_error( $result ) ) {
				$totals['errors']++;
			} elseif ( $result ) {
				$totals['updated']++;
			} else {
				$totals['skipped']++;
			}
		}
	}

	return $totals;
}

/**
 * Keep a single portfolio in sync after its ACF fields are saved.
 *
 * Manually edited content is preserved. Only empty or previously synchronized
 * content is refreshed automatically.
 *
 * @param int|string $post_id ACF post ID.
 */
function bewblur_sync_youtube_description_on_save( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id || 'portfolio' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$youtube_url = get_field( 'youtube_link', $post_id );
	$video_id    = function_exists( 'bewblur_get_youtube_id' ) ? bewblur_get_youtube_id( $youtube_url ) : '';
	if ( ! $video_id || '' === bewblur_get_youtube_api_key() ) {
		return;
	}

	$descriptions = bewblur_fetch_youtube_descriptions( array( $video_id ) );
	if ( is_wp_error( $descriptions ) || ! isset( $descriptions[ $video_id ] ) ) {
		return;
	}

	bewblur_update_portfolio_youtube_description( $post_id, $descriptions[ $video_id ] );
}
add_action( 'acf/save_post', 'bewblur_sync_youtube_description_on_save', 20 );

/**
 * Register the bulk synchronization screen under Portfolios.
 */
function bewblur_register_youtube_sync_page() {
	add_submenu_page(
		'edit.php?post_type=portfolio',
		__( 'Sync YouTube Descriptions', 'bewblur' ),
		__( 'YouTube Sync', 'bewblur' ),
		'edit_posts',
		'bewblur-youtube-sync',
		'bewblur_render_youtube_sync_page'
	);
}
add_action( 'admin_menu', 'bewblur_register_youtube_sync_page' );

/**
 * Render and process the bulk synchronization screen.
 */
function bewblur_render_youtube_sync_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'bewblur' ) );
	}

	$result             = null;
	$api_key_configured = '' !== bewblur_get_youtube_api_key();
	if ( isset( $_POST['bewblur_youtube_sync_submit'] ) ) {
		check_admin_referer( 'bewblur_youtube_sync' );
		$overwrite = ! empty( $_POST['bewblur_youtube_sync_overwrite'] );
		$result    = bewblur_sync_all_youtube_descriptions( $overwrite );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sync YouTube Descriptions', 'bewblur' ); ?></h1>
		<p><?php esc_html_e( 'Copies each linked YouTube video description into its Portfolio content. Empty and previously synced content update safely; manual content is preserved by default.', 'bewblur' ); ?></p>

		<?php if ( ! $api_key_configured ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php
				printf(
					wp_kses(
						__( 'Add your YouTube Data API key in <a href="%s">Theme Settings</a> before syncing.', 'bewblur' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=theme-general-settings' ) )
				);
				?>
			</p></div>
		<?php endif; ?>

		<?php if ( is_wp_error( $result ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
		<?php elseif ( is_array( $result ) ) : ?>
			<div class="notice notice-success inline"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: updated, 2: skipped, 3: missing, 4: errors. */
						__( 'Sync complete: %1$d updated, %2$d preserved, %3$d without descriptions, %4$d errors.', 'bewblur' ),
						$result['updated'],
						$result['skipped'],
						$result['missing'],
						$result['errors']
					)
				);
				?>
			</p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'bewblur_youtube_sync' ); ?>
			<p>
				<label>
					<input type="checkbox" name="bewblur_youtube_sync_overwrite" value="1">
					<?php esc_html_e( 'Replace manually edited Portfolio content with the current YouTube description', 'bewblur' ); ?>
				</label>
			</p>
			<?php
			submit_button(
				__( 'Sync all descriptions', 'bewblur' ),
				'primary',
				'bewblur_youtube_sync_submit',
				true,
				$api_key_configured ? array() : array( 'disabled' => 'disabled' )
			);
			?>
		</form>
	</div>
	<?php
}
