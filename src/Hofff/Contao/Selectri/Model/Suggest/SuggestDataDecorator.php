<?php

namespace Hofff\Contao\Selectri\Model\Suggest;

use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataDelegate;

class SuggestDataDecorator extends DataDelegate {

	/**
	 * @var array
	 */
	private $suggestions;

	/**
	 * @param Data $delegate
	 * @param array $suggestions
	 */
	public function __construct(Data $delegate, array $suggestions = null) {
		parent::__construct($delegate);
		$this->suggestions = (array) $suggestions;
	}

	/**
	 * @return array
	 */
	public function getSuggestions() {
		return $this->suggestions;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::hasSuggestions()
	 */
	public function hasSuggestions() {
		return count($this->suggestions) > 0;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::suggest()
	 */
	public function suggest($limit, $offset = 0) {
		$keys = array_slice($this->suggestions, $offset, $limit);
		return $this->getNodes($keys);
	}

}
