<?php

namespace Lvdl\LittleHotelier\Frontend;

use Lvdl\LittleHotelier\Service\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {
	/**
	 * @var array<int,string>
	 */
	private const SUPPORTED_LANGUAGES = array( 'en', 'es', 'de', 'fr' );

	/**
	 * @var array<string,array<string,string>>
	 */
	private const TRANSLATIONS = array(
		'en' => array(
			'check_in_label'        => 'Check-in',
			'check_out_label'       => 'Check-out',
			'adults_label'          => 'Adults',
			'children_label'        => 'Children',
			'promo_label'           => 'Promo code',
			'button_text'           => 'Check availability',
			'error_checkin_invalid' => 'Check-in date is required and must be valid.',
			'error_checkout_invalid' => 'Check-out date is required and must be valid.',
			'error_checkout_after'  => 'Check-out must be after check-in.',
			'error_stay_max'        => 'Stay cannot exceed {days} days.',
			'error_adults_min'      => 'At least 1 adult is required.',
			'error_guests_negative' => 'Guest counts cannot be negative.',
			'error_generic'         => 'Something went wrong. Please try again.',
		),
		'es' => array(
			'check_in_label'        => 'Entrada',
			'check_out_label'       => 'Salida',
			'adults_label'          => 'Adultos',
			'children_label'        => 'Niños',
			'promo_label'           => 'Código promocional',
			'button_text'           => 'Comprobar disponibilidad',
			'error_checkin_invalid' => 'La fecha de entrada es obligatoria y debe ser válida.',
			'error_checkout_invalid' => 'La fecha de salida es obligatoria y debe ser válida.',
			'error_checkout_after'  => 'La salida debe ser posterior a la entrada.',
			'error_stay_max'        => 'La estancia no puede superar {days} días.',
			'error_adults_min'      => 'Se requiere al menos 1 adulto.',
			'error_guests_negative' => 'El número de huéspedes no puede ser negativo.',
			'error_generic'         => 'Algo salió mal. Inténtalo de nuevo.',
		),
		'de' => array(
			'check_in_label'        => 'Anreise',
			'check_out_label'       => 'Abreise',
			'adults_label'          => 'Erwachsene',
			'children_label'        => 'Kinder',
			'promo_label'           => 'Aktionscode',
			'button_text'           => 'Verfügbarkeit prüfen',
			'error_checkin_invalid' => 'Das Anreisedatum ist erforderlich und muss gültig sein.',
			'error_checkout_invalid' => 'Das Abreisedatum ist erforderlich und muss gültig sein.',
			'error_checkout_after'  => 'Die Abreise muss nach der Anreise liegen.',
			'error_stay_max'        => 'Der Aufenthalt darf {days} Tage nicht überschreiten.',
			'error_adults_min'      => 'Mindestens 1 Erwachsener ist erforderlich.',
			'error_guests_negative' => 'Die Gästeanzahl darf nicht negativ sein.',
			'error_generic'         => 'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.',
		),
		'fr' => array(
			'check_in_label'        => 'Arrivée',
			'check_out_label'       => 'Départ',
			'adults_label'          => 'Adultes',
			'children_label'        => 'Enfants',
			'promo_label'           => 'Code promo',
			'button_text'           => 'Vérifier la disponibilité',
			'error_checkin_invalid' => 'La date d\'arrivée est obligatoire et doit être valide.',
			'error_checkout_invalid' => 'La date de départ est obligatoire et doit être valide.',
			'error_checkout_after'  => 'La date de départ doit être postérieure à la date d\'arrivée.',
			'error_stay_max'        => 'Le séjour ne peut pas dépasser {days} jours.',
			'error_adults_min'      => 'Au moins 1 adulte est requis.',
			'error_guests_negative' => 'Le nombre de voyageurs ne peut pas être négatif.',
			'error_generic'         => 'Une erreur est survenue. Veuillez réessayer.',
		),
	);

	private SettingsRepository $settings_repo;
	private Assets $assets;

	public function __construct( SettingsRepository $settings_repo ) {
		$this->settings_repo = $settings_repo;
		$this->assets        = new Assets();
		add_shortcode( 'lvdl_lh_datepicker', array( $this, 'render' ) );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	public function render( array $atts = array() ): string {
		$settings = $this->settings_repo->get_settings();
		$atts     = shortcode_atts(
			array(
				'channel_code' => (string) $settings['channel_code'],
				'show_guests'  => 'true',
				'show_promo'   => 'false',
				'button_text'  => '',
				'currency'     => (string) $settings['currency'],
				'locale'       => '',
				'language'     => '',
				'layout'       => 'grid',
				'color'        => '',
				'title'        => '',
			),
			$atts,
			'lvdl_lh_datepicker'
		);

		$language     = $this->resolve_language( (string) $atts['language'], (string) $atts['locale'] );
		$translations = $this->get_translations( $language );
		$button_text  = trim( sanitize_text_field( (string) $atts['button_text'] ) );
		if ( '' === $button_text ) {
			$button_text = $translations['button_text'];
		}

		$config = array(
			'maxStayDays' => absint( $settings['max_stay_days'] ),
		);
		$this->assets->enqueue_widget_assets( $config );

		$context = array(
			'form_id'      => wp_unique_id( 'lvdl-lh-' ),
			'channel_code' => sanitize_text_field( (string) $atts['channel_code'] ),
			'show_guests'  => $this->to_bool( (string) $atts['show_guests'] ),
			'show_promo'   => $this->to_bool( (string) $atts['show_promo'] ),
			'button_text'  => $button_text,
			'currency'     => sanitize_text_field( (string) $atts['currency'] ),
			'locale'       => $language,
			'language'     => $language,
			'title'        => sanitize_text_field( (string) $atts['title'] ),
			'text_color'   => $this->sanitize_color_value( (string) $atts['color'] ),
			'i18n'         => $translations,
		);

		ob_start();
		require LVDL_LH_PLUGIN_DIR . 'templates/widget-form.php';
		return (string) ob_get_clean();
	}

	private function to_bool( string $value ): bool {
		return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	private function sanitize_color_value( string $value ): string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return '';
		}

		if ( 1 === preg_match( '/^#[A-Fa-f0-9]{3,8}$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^(?:rgb|rgba|hsl|hsla)\([^()]+\)$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^var\(--[A-Za-z0-9\-_]+\)$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^[A-Za-z]+$/', $value ) ) {
			return strtolower( $value );
		}

		return '';
	}

	private function resolve_language( string $language, string $locale ): string {
		$normalized_language = $this->normalize_language( $language );
		if ( '' !== $normalized_language ) {
			return $normalized_language;
		}

		$normalized_locale = $this->normalize_language( $locale );
		if ( '' !== $normalized_locale ) {
			return $normalized_locale;
		}

		return 'en';
	}

	private function normalize_language( string $value ): string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return '';
		}

		$base = strtolower( substr( str_replace( '_', '-', $value ), 0, 2 ) );
		if ( ! in_array( $base, self::SUPPORTED_LANGUAGES, true ) ) {
			return '';
		}

		return $base;
	}

	/**
	 * @return array<string,string>
	 */
	private function get_translations( string $language ): array {
		if ( isset( self::TRANSLATIONS[ $language ] ) ) {
			return self::TRANSLATIONS[ $language ];
		}

		return self::TRANSLATIONS['en'];
	}
}
