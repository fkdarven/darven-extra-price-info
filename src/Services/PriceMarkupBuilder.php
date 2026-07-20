<?php

namespace Darven\ExtraPriceInfo\Services;

final class PriceMarkupBuilder {
	public function div( string $classes, string $content, string $type ): string {
		return sprintf( '<div class="%s %s">%s</div>', $classes, $this->getPageClass( $type ), $content );
	}

	public function span( string $classes, string $content, string $type ): string {
		return sprintf( '<span class="%s %s"> %s </span>', $classes, $this->getPageClass( $type ), $content );
	}

	private function getPageClass( string $type ): string {
		if ( is_product() ) {
			return $type . '-epi-single-product';
		}

		if ( is_shop() ) {
			return $type . '-epi-shop';
		}

		if ( is_category() || is_product_category() ) {
			return $type . '-epi-category';
		}

		if ( is_search() ) {
			return $type . '-epi-search';
		}

		if ( is_home() ) {
			return $type . '-epi-home';
		}

		return $type;
	}
}
