<?php

namespace Lvdl\LittleHotelier\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingRequest {
	private DateRange $date_range;
	private GuestCounts $guest_counts;
	private string $currency;
	private string $locale;
	private string $promo_code;
	private string $track_page;
	private string $channel_code;

	public function __construct(
		DateRange $date_range,
		GuestCounts $guest_counts,
		string $currency,
		string $locale,
		string $promo_code,
		string $track_page,
		string $channel_code
	) {
		$this->date_range   = $date_range;
		$this->guest_counts = $guest_counts;
		$this->currency     = $this->sanitize_currency( $currency );
		$this->locale       = $this->sanitize_locale( $locale );
		$this->promo_code   = $this->sanitize_promo( $promo_code );
		$this->track_page   = $this->sanitize_track_page( $track_page );
		$this->channel_code = sanitize_text_field( $channel_code );
	}

	public function date_range(): DateRange {
		return $this->date_range;
	}

	public function guest_counts(): GuestCounts {
		return $this->guest_counts;
	}

	public function currency(): string {
		return $this->currency;
	}

	public function locale(): string {
		return $this->locale;
	}

	public function promo_code(): string {
		return $this->promo_code;
	}

	public function track_page(): string {
		return $this->track_page;
	}

	public function channel_code(): string {
		return $this->channel_code;
	}

	private function sanitize_currency( string $currency ): string {
		$currency = strtoupper( sanitize_text_field( $currency ) );

		if ( 1 !== preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			throw new ValidationException(
				__( 'Invalid currency format.', 'lvdl-little-hotelier' ),
				array( 'currency' => __( 'Currency must be a 3-letter ISO code.', 'lvdl-little-hotelier' ) )
			);
		}

		return $currency;
	}

	private function sanitize_locale( string $locale ): string {
		$locale = sanitize_text_field( $locale );

		if ( 1 !== preg_match( '/^[a-z]{2}(?:[_-][A-Z]{2})?$/', $locale ) ) {
			throw new ValidationException(
				__( 'Invalid locale format.', 'lvdl-little-hotelier' ),
				array( 'locale' => __( 'Locale must be like en or en_US.', 'lvdl-little-hotelier' ) )
			);
		}

		return $locale;
	}

	private function sanitize_promo( string $promo ): string {
		$promo = sanitize_text_field( $promo );
		$promo = substr( $promo, 0, 32 );

		return $promo;
	}

	private function sanitize_track_page( string $track_page ): string {
		$track_page = strtolower( sanitize_text_field( $track_page ) );

		if ( ! in_array( $track_page, array( 'yes', 'no' ), true ) ) {
			$track_page = 'yes';
		}

		return $track_page;
	}
}
