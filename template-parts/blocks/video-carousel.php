<?php
/**
 * Video Carousel block.
 *
 * @package bewblur
 */

if ( ! function_exists( 'bewblur_get_youtube_id' ) ) {
	/**
	 * Extract YouTube video ID from a URL string.
	 *
	 * @param string $url YouTube watch or youtu.be URL.
	 * @return string
	 */
	function bewblur_get_youtube_id( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return '';
		}

		$url = trim( $url );

		if ( preg_match( '#youtu\.be/([^?&/]+)#i', $url, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}
		if ( preg_match( '#[?&]v=([^&]+)#i', $url, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}
		if ( preg_match( '#youtube\.com/embed/([^?&/]+)#i', $url, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}

		return '';
	}
}

$slides       = get_field( 'video_carousel' );
$slide_height = get_field( 'carousel_slide_height' );
$slide_height = is_numeric( $slide_height ) ? (int) $slide_height : 400;
$slide_height = max( 100, min( 1200, $slide_height ) );

if ( empty( $slides ) || ! is_array( $slides ) ) {
	return;
}

$items = array();
foreach ( $slides as $slide ) {
	$thumb = ! empty( $slide['video_thumbnail'] ) ? $slide['video_thumbnail'] : '';
	$file  = ! empty( $slide['video_file'] ) ? $slide['video_file'] : '';
	$yt    = ! empty( $slide['youtube_url'] ) ? trim( $slide['youtube_url'] ) : '';

	if ( empty( $thumb ) ) {
		continue;
	}

	$items[] = array(
		'thumb'       => $thumb,
		'file'        => $file,
		'youtube_id'  => bewblur_get_youtube_id( $yt ),
	);
}

if ( empty( $items ) ) {
	return;
}

$class_name = 'video-carousel-block';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . sanitize_html_class( $block['className'] );
}
if ( ! empty( $block['align'] ) ) {
	$class_name .= ' align' . sanitize_html_class( $block['align'] );
}

$block_id   = ! empty( $block['id'] ) ? $block['id'] : wp_unique_id( 'video-carousel-' );
$popover_id = 'video-carousel-popover-' . $block_id;
?>

<div
	class="<?php echo esc_attr( $class_name ); ?>"
	style="<?php echo esc_attr( '--video-carousel-slide-height: ' . $slide_height . 'px;' ); ?>"
>
	<span class="video-carousel__edge-fade video-carousel__edge-fade--left" aria-hidden="true"></span>
	<span class="video-carousel__edge-fade video-carousel__edge-fade--right" aria-hidden="true"></span>
	<div
		class="video-carousel swiper"
		data-popover-id="<?php echo esc_attr( $popover_id ); ?>"
	>
		<div class="swiper-wrapper">
			<?php foreach ( $items as $item ) : ?>
				<div class="swiper-slide">
					<div
						class="video-carousel__item"
						role="button"
						tabindex="0"
						<?php echo $item['youtube_id'] ? ' data-youtube-id="' . esc_attr( $item['youtube_id'] ) . '"' : ''; ?>
						aria-label="<?php esc_attr_e( 'Play video', 'bewblur' ); ?>"
					>
						<span class="video-carousel__media">
							<img
								class="video-carousel__thumb"
								src="<?php echo esc_url( $item['thumb'] ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
								draggable="false"
							>
							<?php if ( ! empty( $item['file'] ) ) : ?>
								<video
									class="video-carousel__preview"
									src="<?php echo esc_url( $item['file'] ); ?>"
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
</div>
