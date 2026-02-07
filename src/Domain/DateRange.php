<?php

namespace Lvdl\LittleHotelier\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DateRange {
	private string $check_in;
	private string $check_out;
	private int $max_stay_days;

	public function __construct( string $check_in, string $check_out, int $max_stay_days ) {
		$this->check_in      = $check_in;
		$this->check_out     = $check_out;
		$this->max_stay_days = $max_stay_days;

		$this->validate();
	}

	public function check_in(): string {
		return $this->check_in;
	}

	public function check_out(): string {
		return $this->check_out;
	}

	private function validate(): void {
		$check_in  = \DateTimeImmutable::createFromFormat( 'Y-m-d', $this->check_in );
		$check_out = \DateTimeImmutable::createFromFormat( 'Y-m-d', $this->check_out );

		$errors = array();

		if ( ! $check_in || $check_in->format( 'Y-m-d' ) !== $this->check_in ) {
			$errors['checkInDate'] = __( 'Invalid check-in date format.', 'lvdl-little-hotelier' );
		}

		if ( ! $check_out || $check_out->format( 'Y-m-d' ) !== $this->check_out ) {
			$errors['checkOutDate'] = __( 'Invalid check-out date format.', 'lvdl-little-hotelier' );
		}

		if ( ! empty( $errors ) ) {
			throw new ValidationException( __( 'Invalid date input.', 'lvdl-little-hotelier' ), $errors );
		}

		if ( $check_out <= $check_in ) {
			throw new ValidationException(
				__( 'Check-out must be after check-in.', 'lvdl-little-hotelier' ),
				array( 'checkOutDate' => __( 'Check-out must be after check-in.', 'lvdl-little-hotelier' ) )
			);
		}

		$interval = $check_in->diff( $check_out );
		$days     = (int) $interval->days;

		if ( $days > $this->max_stay_days ) {
			throw new ValidationException(
				sprintf(
					/* translators: %d: max stay days */
					__( 'Stay length cannot exceed %d days.', 'lvdl-little-hotelier' ),
					$this->max_stay_days
				),
				array( 'checkOutDate' => __( 'Stay exceeds maximum allowed length.', 'lvdl-little-hotelier' ) )
			);
		}
	}
}
