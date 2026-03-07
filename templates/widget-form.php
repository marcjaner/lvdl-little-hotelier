<?php
/**
 * @var array<string,mixed> $context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$style_attr   = '';
$form_classes = 'lvdl-lh-widget';
$i18n         = is_array( $context['i18n'] ?? null ) ? $context['i18n'] : array();
if ( ! empty( $context['text_color'] ) ) {
	$style_attr = '--lvdl-lh-text-color:' . $context['text_color'] . ';';
	$form_classes .= ' lvdl-lh-has-color';
}
?>
<form
	class="<?php echo esc_attr( $form_classes ); ?>"
	<?php echo '' !== $style_attr ? ' style="' . esc_attr( $style_attr ) . '"' : ''; ?>
	data-i18n-check-in-invalid="<?php echo esc_attr( (string) ( $i18n['error_checkin_invalid'] ?? '' ) ); ?>"
	data-i18n-check-out-invalid="<?php echo esc_attr( (string) ( $i18n['error_checkout_invalid'] ?? '' ) ); ?>"
	data-i18n-check-out-after="<?php echo esc_attr( (string) ( $i18n['error_checkout_after'] ?? '' ) ); ?>"
	data-i18n-stay-max="<?php echo esc_attr( (string) ( $i18n['error_stay_max'] ?? '' ) ); ?>"
	data-i18n-adults-min="<?php echo esc_attr( (string) ( $i18n['error_adults_min'] ?? '' ) ); ?>"
	data-i18n-guests-negative="<?php echo esc_attr( (string) ( $i18n['error_guests_negative'] ?? '' ) ); ?>"
	data-i18n-generic-error="<?php echo esc_attr( (string) ( $i18n['error_generic'] ?? '' ) ); ?>"
	novalidate
>
	<?php if ( ! empty( $context['title'] ) ) : ?>
		<h3 class="lvdl-lh-title"><?php echo esc_html( (string) $context['title'] ); ?></h3>
	<?php endif; ?>

	<div class="lvdl-lh-form-grid">
		<div class="lvdl-lh-field">
			<label for="<?php echo esc_attr( $context['form_id'] ); ?>-checkin"><?php echo esc_html( (string) ( $i18n['check_in_label'] ?? 'Check-in' ) ); ?></label>
			<input id="<?php echo esc_attr( $context['form_id'] ); ?>-checkin" name="checkInDate" type="date" required />
		</div>
		<div class="lvdl-lh-field">
			<label for="<?php echo esc_attr( $context['form_id'] ); ?>-checkout"><?php echo esc_html( (string) ( $i18n['check_out_label'] ?? 'Check-out' ) ); ?></label>
			<input id="<?php echo esc_attr( $context['form_id'] ); ?>-checkout" name="checkOutDate" type="date" required />
		</div>
		<?php if ( ! empty( $context['show_guests'] ) ) : ?>
			<div class="lvdl-lh-field">
				<label for="<?php echo esc_attr( $context['form_id'] ); ?>-adults"><?php echo esc_html( (string) ( $i18n['adults_label'] ?? 'Adults' ) ); ?></label>
				<input id="<?php echo esc_attr( $context['form_id'] ); ?>-adults" name="adults" type="number" min="1" value="2" required />
			</div>
			<div class="lvdl-lh-field">
				<label for="<?php echo esc_attr( $context['form_id'] ); ?>-children"><?php echo esc_html( (string) ( $i18n['children_label'] ?? 'Children' ) ); ?></label>
				<input id="<?php echo esc_attr( $context['form_id'] ); ?>-children" name="children" type="number" min="0" value="0" />
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $context['show_promo'] ) ) : ?>
			<div class="lvdl-lh-field">
				<label for="<?php echo esc_attr( $context['form_id'] ); ?>-promocode"><?php echo esc_html( (string) ( $i18n['promo_label'] ?? 'Promo code' ) ); ?></label>
				<input id="<?php echo esc_attr( $context['form_id'] ); ?>-promocode" name="promocode" type="text" maxlength="32" />
			</div>
		<?php endif; ?>
		<button type="submit" class="lvdl-lh-button"><?php echo esc_html( (string) $context['button_text'] ); ?></button>
	</div>

	<input name="currency" type="hidden" value="<?php echo esc_attr( (string) $context['currency'] ); ?>" />
	<input name="locale" type="hidden" value="<?php echo esc_attr( (string) $context['locale'] ); ?>" />
	<input name="channel_code" type="hidden" value="<?php echo esc_attr( (string) $context['channel_code'] ); ?>" />
	<input name="trackPage" type="hidden" value="yes" />
	<?php if ( ! empty( $context['show_guests'] ) ) : ?>
		<input name="infants" type="hidden" value="0" />
	<?php else : ?>
		<input name="adults" type="hidden" value="2" />
		<input name="children" type="hidden" value="0" />
		<input name="infants" type="hidden" value="0" />
	<?php endif; ?>

	<div class="lvdl-lh-errors" aria-live="polite"></div>
</form>
