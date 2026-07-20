<?php
/**
 * Marquee block — seamless horizontal scroll.
 *
 * @package bewblur
 */

$marquee = get_field( 'marquee' );
if ( empty( $marquee ) || ! is_array( $marquee ) ) {
	return;
}

$items = array();
foreach ( $marquee as $row ) {
	if ( empty( $row['marquee_item'] ) ) {
		continue;
	}
	$text = trim( $row['marquee_item'] );
	if ( $text !== '' ) {
		$items[] = $text;
	}
}

if ( empty( $items ) ) {
	return;
}

$class_name = 'block-marquee';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . sanitize_html_class( $block['className'] );
}
if ( ! empty( $block['align'] ) ) {
	$class_name .= ' align' . sanitize_html_class( $block['align'] );
}

$separator_svg = '<svg width="54" height="54" viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M19.2781 53.1098L22.0874 34.3422L7.1501 46.0877L0 33.5759L16.9804 26.2991L0 19.5327L7.1501 7.021L21.9595 18.256L19.2781 0H33.8329L31.6619 17.8734L45.9609 7.021L53.2377 19.5327L36.0027 26.299L53.2377 33.5758L45.9609 46.0875L31.534 34.7258L33.8329 53.1097L19.2781 53.1098Z" fill="#111111"/></svg>';

$render_sequence = static function () use ( $items, $separator_svg ) {
	foreach ( $items as $item ) {
		echo '<span class="block-marquee__item">' . esc_html( $item ) . '</span>';
		echo '<span class="block-marquee__sep">' . $separator_svg . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG markup
	}
};
?>

<div class="<?php echo esc_attr( $class_name ); ?>">
	<div class="block-marquee__viewport">
		<div class="block-marquee__track">
			<div class="block-marquee__group">
				<?php $render_sequence(); ?>
			</div>
			<div class="block-marquee__group" aria-hidden="true">
				<?php $render_sequence(); ?>
			</div>
		</div>
	</div>
</div>
