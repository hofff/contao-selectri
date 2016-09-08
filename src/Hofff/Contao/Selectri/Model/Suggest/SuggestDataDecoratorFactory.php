<?php

namespace Hofff\Contao\Selectri\Model\Suggest;

use Hofff\Contao\Selectri\Model\AbstractDataDecoratorFactory;
use Hofff\Contao\Selectri\Model\Data;

class SuggestDataDecoratorFactory extends AbstractDataDecoratorFactory {

	/**
	 * @var callable|null
	 */
	private $suggestionCallback;

	/**
	 * @param callable $suggestionCallback
	 * @return void
	 */
	public function setSuggestionCallback(callable $suggestionCallback) {
		$this->suggestionCallback = $suggestionCallback;
	}

	/**
	 * @return array
	 */
	protected function fetchSuggestions() {
		return $this->suggestionCallback ? call_user_func($this->suggestionCallback) : null;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractDataDecoratorFactory::createDecorator()
	 */
	public function createDecorator(Data $decoratedData) {
		$suggestions = $this->fetchSuggestions();
		return new SuggestDataDecorator($decoratedData, $suggestions);
	}

}
