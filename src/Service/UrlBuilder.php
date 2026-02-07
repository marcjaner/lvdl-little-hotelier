<?php

namespace Lvdl\LittleHotelier\Service;

use Lvdl\LittleHotelier\Domain\BookingRequest;
use Lvdl\LittleHotelier\Domain\ValidationException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UrlBuilder {
	public function build( string $base_url_template, BookingRequest $request ): string {
		$base_url_template = trim( $base_url_template );

		if ( str_contains( $base_url_template, '{channel_code}' ) ) {
			if ( '' === $request->channel_code() ) {
				throw new ValidationException(
					__( 'Channel code is required for this booking URL.', 'lvdl-little-hotelier' ),
					array( 'channel_code' => __( 'Channel code cannot be empty.', 'lvdl-little-hotelier' ) )
				);
			}

			$base_url_template = str_replace( '{channel_code}', rawurlencode( $request->channel_code() ), $base_url_template );
		}

		$base_url_template = esc_url_raw( $base_url_template );

		if ( empty( $base_url_template ) ) {
			throw new ValidationException(
				__( 'Booking base URL is not configured.', 'lvdl-little-hotelier' ),
				array( 'base_url_template' => __( 'Set the Little Hotelier base URL in plugin settings.', 'lvdl-little-hotelier' ) )
			);
		}

		$parsed = wp_parse_url( $base_url_template );
		if ( empty( $parsed['scheme'] ) || 'https' !== strtolower( $parsed['scheme'] ) ) {
			throw new ValidationException(
				__( 'Only HTTPS booking URLs are allowed.', 'lvdl-little-hotelier' ),
				array( 'base_url_template' => __( 'Base URL must start with https://', 'lvdl-little-hotelier' ) )
			);
		}

		$query = array(
			'checkInDate'       => $request->date_range()->check_in(),
			'checkOutDate'      => $request->date_range()->check_out(),
			'items'             => array(
				array(
					'adults'   => $request->guest_counts()->adults(),
					'children' => $request->guest_counts()->children(),
					'infants'  => $request->guest_counts()->infants(),
				),
			),
			'currency'          => $request->currency(),
			'locale'            => $request->locale(),
			'trackPage'         => $request->track_page(),
		);

		if ( '' !== $request->promo_code() ) {
			$query['promocode'] = $request->promo_code();
		}

		$serialized = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );

		$separator = ( false === strpos( $base_url_template, '?' ) ) ? '?' : '&';

		return $base_url_template . $separator . $serialized;
	}
}
