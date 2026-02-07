<?php

namespace Lvdl\LittleHotelier\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ValidationException extends \RuntimeException {
	/**
	 * @var array<string,string>
	 */
	private array $field_errors;

	/**
	 * @param array<string,string> $field_errors
	 */
	public function __construct( string $message, array $field_errors = array() ) {
		parent::__construct( $message );
		$this->field_errors = $field_errors;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_field_errors(): array {
		return $this->field_errors;
	}
}
