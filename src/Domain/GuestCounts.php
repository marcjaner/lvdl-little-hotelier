<?php

namespace Lvdl\LittleHotelier\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GuestCounts {
	private int $adults;
	private int $children;
	private int $infants;

	public function __construct( int $adults, int $children, int $infants ) {
		$this->adults   = $adults;
		$this->children = $children;
		$this->infants  = $infants;

		$this->validate();
	}

	public function adults(): int {
		return $this->adults;
	}

	public function children(): int {
		return $this->children;
	}

	public function infants(): int {
		return $this->infants;
	}

	private function validate(): void {
		$errors = array();

		if ( $this->adults < 1 ) {
			$errors['adults'] = __( 'At least 1 adult is required.', 'lvdl-little-hotelier' );
		}

		if ( $this->children < 0 ) {
			$errors['children'] = __( 'Children cannot be negative.', 'lvdl-little-hotelier' );
		}

		if ( $this->infants < 0 ) {
			$errors['infants'] = __( 'Infants cannot be negative.', 'lvdl-little-hotelier' );
		}

		if ( ! empty( $errors ) ) {
			throw new ValidationException( __( 'Invalid guest counts.', 'lvdl-little-hotelier' ), $errors );
		}
	}
}
