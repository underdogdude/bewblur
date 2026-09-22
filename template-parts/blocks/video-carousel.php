<?php
/**
 * Video Carousel block.
 *
 * @package bewblur
 */

if ( ! function_exists( 'bewblur_get_youtube_id' ) ) {
	/**
	 * Extract a YouTube video ID from a URL string.
	 *
	 * @param string $url YouTube URL.
	 * @return string
	 */
	function bewblur_get_youtube_id( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return '';
		}

		$url = trim( $url );
		$patterns = array(
			'#youtu\.be/([^?&/]+)#i',
			'#[?&]v=([^&]+)#i',
			'#youtube\.com/(?:embed|shorts|live)/([^?&/]+)#i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return sanitize_text_field( $matches[1] );
			}
		}

		return '';
	}
}

$portfolio_type = get_field( 'video_carousel_portfolio_type' );
$row_count       = (int) get_field( 'video_carousel_rows' );
$row_count       = 2 === $row_count ? 2 : 1;

$query_args = array(
	'post_type'              => 'portfolio',
	'post_status'            => 'publish',
	'posts_per_page'         => 16,
	'orderby'                => 'rand',
	'no_found_rows'          => true,
	'ignore_sticky_posts'    => true,
	'update_post_term_cache' => false,
);

if ( ! empty( $portfolio_type ) && 'all' !== $portfolio_type ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'portfolio-type',
			'field'    => 'slug',
			'terms'    => sanitize_title( $portfolio_type ),
		),
	);
}

$portfolio_query = new WP_Query( $query_args );
$items           = array();

foreach ( $portfolio_query->posts as $portfolio ) {
	$post_id      = $portfolio->ID;
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	$video_teaser = get_field( 'video_teaser', $post_id );
	$video_id     = get_field( 'video_teaser', $post_id, false );
	$youtube_url  = get_field( 'youtube_link', $post_id );
	$video_ratio  = '';

	if ( is_array( $video_teaser ) ) {
		$video_teaser = isset( $video_teaser['url'] ) ? $video_teaser['url'] : '';
	} elseif ( is_numeric( $video_teaser ) ) {
		$video_teaser = wp_get_attachment_url( (int) $video_teaser );
	}

	if ( is_numeric( $video_id ) ) {
		$video_metadata = wp_get_attachment_metadata( (int) $video_id );
		if ( ! empty( $video_metadata['width'] ) && ! empty( $video_metadata['height'] ) ) {
			$video_ratio = absint( $video_metadata['width'] ) . ' / ' . absint( $video_metadata['height'] );
		}
	}

	$items[] = array(
		'title'        => get_the_title( $post_id ),
		'permalink'    => get_permalink( $post_id ),
		'thumbnail_id' => $thumbnail_id,
		'thumb_url'    => $thumbnail_id ? '' : get_theme_file_uri( '/img/thumb.jpg' ),
		'video_teaser' => $video_teaser,
		'video_ratio'  => $video_ratio,
		'youtube_id'   => bewblur_get_youtube_id( $youtube_url ),
	);
}

if ( empty( $items ) ) {
	return;
}

$rows = array( $items );
if ( 2 === $row_count ) {
	$split_at = (int) ceil( count( $items ) / 2 );
	$rows     = array(
		array_slice( $items, 0, $split_at ),
		array_slice( $items, $split_at ),
	);
}

$class_name = 'video-carousel-block video-carousel-block--' . $row_count . '-row';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . sanitize_html_class( $block['className'] );
}
if ( ! empty( $block['align'] ) ) {
	$class_name .= ' align' . sanitize_html_class( $block['align'] );
}

$block_id   = ! empty( $block['id'] ) ? $block['id'] : wp_unique_id( 'video-carousel-' );
$popover_id = 'video-carousel-popover-' . $block_id;
?>

<div class="<?php echo esc_attr( $class_name ); ?>">
	<?php foreach ( $rows as $row_index => $row_items ) : ?>
		<?php
		if ( empty( $row_items ) ) {
			continue;
		}

		$row_number = $row_index + 1;
		$row_height = 1 === $row_count ? 220 : ( 1 === $row_number ? 410 : 220 );
		?>
		<div
			class="video-carousel__row video-carousel__row--<?php echo esc_attr( $row_number ); ?>"
			style="<?php echo esc_attr( '--video-carousel-slide-height: ' . $row_height . 'px;' ); ?>"
		>
			<span class="video-carousel__edge-fade video-carousel__edge-fade--left" aria-hidden="true"></span>
			<span class="video-carousel__edge-fade video-carousel__edge-fade--right" aria-hidden="true"></span>
			<div
				class="video-carousel swiper"
				data-popover-id="<?php echo esc_attr( $popover_id ); ?>"
			>
				<div class="swiper-wrapper">
					<?php foreach ( $row_items as $item ) : ?>
						<div class="swiper-slide">
							<div
								class="video-carousel__item"
								<?php if ( $item['youtube_id'] ) : ?>
									role="button"
									tabindex="0"
									data-youtube-id="<?php echo esc_attr( $item['youtube_id'] ); ?>"
									data-portfolio-title="<?php echo esc_attr( $item['title'] ); ?>"
									data-portfolio-url="<?php echo esc_url( $item['permalink'] ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Play %s', 'bewblur' ), $item['title'] ) ); ?>"
								<?php endif; ?>
							>
								<span
									class="video-carousel__media<?php echo $item['video_ratio'] ? ' has-video-ratio' : ''; ?>"
									<?php if ( $item['video_ratio'] ) : ?>
										style="<?php echo esc_attr( 'aspect-ratio: ' . $item['video_ratio'] . ';' ); ?>"
									<?php endif; ?>
								>
									<?php if ( $item['thumbnail_id'] ) : ?>
										<?php
										echo wp_get_attachment_image(
											$item['thumbnail_id'],
											'full',
											false,
											array(
												'class'     => 'video-carousel__thumb',
												'loading'   => 'lazy',
												'decoding'  => 'async',
												'draggable' => 'false',
											)
										); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by WordPress.
										?>
									<?php else : ?>
										<img
											class="video-carousel__thumb"
											src="<?php echo esc_url( $item['thumb_url'] ); ?>"
											alt="<?php echo esc_attr( $item['title'] ); ?>"
											loading="lazy"
											decoding="async"
											draggable="false"
										>
									<?php endif; ?>
									<?php if ( ! empty( $item['video_teaser'] ) ) : ?>
										<video
											class="video-carousel__preview"
											src="<?php echo esc_url( $item['video_teaser'] ); ?>"
											muted
											loop
											playsinline
											preload="metadata"
										></video>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<div
	id="<?php echo esc_attr( $popover_id ); ?>"
	class="video-carousel-popover"
	popover
>
	<button
		type="button"
		class="video-carousel-popover__close"
		popovertarget="<?php echo esc_attr( $popover_id ); ?>"
		popovertargetaction="hide"
		aria-label="<?php esc_attr_e( 'Close', 'bewblur' ); ?>"
	><?php seed_icon( 'x' ); ?></button>
	<div class="video-carousel-popover__frame">
		<iframe
			class="video-carousel-popover__iframe"
			title="<?php esc_attr_e( 'YouTube video player', 'bewblur' ); ?>"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
			allowfullscreen
		></iframe>
	</div>
	<div class="video-carousel-popover__details">
		<p class="video-carousel-popover__title"></p>
		<a class="video-carousel-popover__link" href="#">
			<?php esc_html_e( 'View project', 'bewblur' ); ?>
		</a>
	</div>
</div>
