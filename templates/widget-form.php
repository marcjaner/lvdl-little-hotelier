<?php
/**
 * @var array<string,mixed> $context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$layout_class = 'inline' === $context['layout'] ? 'lvdl-lh-layout-inline' : 'lvdl-lh-layout-grid';
$style_attr   = '';
if ( ! empty( $context['text_color'] ) ) {
	$style_attr = '--lvdl-lh-text-color:' . $context['text_color'] . ';';
}
?>
<form class="lvdl-lh-widget <?php echo esc_attr( $layout_class ); ?>"<?php echo '' !== $style_attr ? ' style="' . esc_attr( $style_attr ) . '"' : ''; ?> novalidate>
	<?php if ( ! empty( $context['title'] ) ) : ?>
		<h3 class="lvdl-lh-title"><?php echo esc_html( (string) $context['title'] ); ?></h3>
	<?php endif; ?>

	<div class="lvdl-lh-grid">
		<div class="lvdl-lh-field">
			<label for="<?php echo esc_attr( $context['form_id'] ); ?>-checkin"><?php esc_html_e( 'Check-in', 'lvdl-little-hotelier' ); ?></label>
			<input id="<?php echo esc_attr( $context['form_id'] ); ?>-checkin" name="checkInDate" type="date" required />
		</div>
		<div class="lvdl-lh-field">
			<label for="<?php echo esc_attr( $context['form_id'] ); ?>-checkout"><?php esc_html_e( 'Check-out', 'lvdl-little-hotelier' ); ?></label>
			<input id="<?php echo esc_attr( $context['form_id'] ); ?>-checkout" name="checkOutDate" type="date" required />
		</div>
	</div>

	<?php if ( ! empty( $context['show_guests'] ) ) : ?>
		<div class="lvdl-lh-grid lvdl-lh-grid-guests">
			<div class="lvdl-lh-field">
				<label for="<?php echo esc_attr( $context['form_id'] ); ?>-adults"><?php esc_html_e( 'Adults', 'lvdl-little-hotelier' ); ?></label>
				<input id="<?php echo esc_attr( $context['form_id'] ); ?>-adults" name="adults" type="number" min="1" value="2" required />
			</div>
			<div class="lvdl-lh-field">
				<label for="<?php echo esc_attr( $context['form_id'] ); ?>-children"><?php esc_html_e( 'Children', 'lvdl-little-hotelier' ); ?></label>
				<input id="<?php echo esc_attr( $context['form_id'] ); ?>-children" name="children" type="number" min="0" value="0" />
			</div>

			<?php if ( 'inline' !== $context['layout'] ) : ?>
				<div class="lvdl-lh-field">
					<label for="<?php echo esc_attr( $context['form_id'] ); ?>-infants"><?php esc_html_e( 'Infants', 'lvdl-little-hotelier' ); ?></label>
					<input id="<?php echo esc_attr( $context['form_id'] ); ?>-infants" name="infants" type="number" min="0" value="0" />
				</div>
			<?php endif; ?>
		</div>
		<?php if ( 'inline' === $context['layout'] ) : ?>
			<input name="infants" type="hidden" value="0" />
		<?php endif; ?>
	<?php else : ?>
		<input name="adults" type="hidden" value="2" />
		<input name="children" type="hidden" value="0" />
		<input name="infants" type="hidden" value="0" />
	<?php endif; ?>

	<?php if ( ! empty( $context['show_promo'] ) ) : ?>
		<div class="lvdl-lh-field">
			<label for="<?php echo esc_attr( $context['form_id'] ); ?>-promocode"><?php esc_html_e( 'Promo code', 'lvdl-little-hotelier' ); ?></label>
			<input id="<?php echo esc_attr( $context['form_id'] ); ?>-promocode" name="promocode" type="text" maxlength="32" />
		</div>
	<?php endif; ?>

	<input name="currency" type="hidden" value="<?php echo esc_attr( (string) $context['currency'] ); ?>" />
	<input name="locale" type="hidden" value="<?php echo esc_attr( (string) $context['locale'] ); ?>" />
	<input name="channel_code" type="hidden" value="<?php echo esc_attr( (string) $context['channel_code'] ); ?>" />
	<input name="trackPage" type="hidden" value="yes" />

	<div class="lvdl-lh-errors" aria-live="polite"></div>
	<button type="submit" class="lvdl-lh-button"><?php echo esc_html( (string) $context['button_text'] ); ?></button>
</form>
