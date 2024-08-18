<?php

	namespace Darven\Epi\Core\Components\Providers;

	use Darven\Epi\Abstracts\AbstractEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PriceProvider {
	private $price;

	public function __construct( AbstractEntity $price ) {
		$this->price = $price;
	}

	public function shouldShow() {
		return $this->price->shouldShow();
	}

	public function getPriceData() {
		if ( ! $this->shouldShow() ) {
			return array();
		}

		$maximumInstallments = $this->price->getMaximumInstallments();
		return array(
			'prefix'              => $this->price->prefix,
			'suffix'              => $this->price->suffix,
			'prefixColor'         => $this->price->prefix_color,
			'prefixSize'          => $this->price->prefix_size,
			'suffixColor'         => $this->price->suffix_color,
			'maximumInstallments' => $maximumInstallments,
			'interest'            => $this->price->getInterest( $maximumInstallments ),
			'interestFeeFrom'     => $this->price->interest_fee_from,
		);
	}
}


