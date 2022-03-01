<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Suggest;

use Hofff\Contao\Selectri\Model\AbstractDataDecoratorFactory;
use Hofff\Contao\Selectri\Model\Data;

use function call_user_func;

class SuggestDataDecoratorFactory extends AbstractDataDecoratorFactory
{
    /** @var callable<list<string>>|null */
    private $suggestionCallback;

    /** @param callable< list<string>> $suggestionCallback */
    public function setSuggestionCallback(callable $suggestionCallback): void
    {
        $this->suggestionCallback = $suggestionCallback;
    }

    /**
     * @return list<string>|null
     */
    protected function fetchSuggestions(): ?array
    {
        return $this->suggestionCallback ? call_user_func($this->suggestionCallback) : null;
    }

    public function createDecorator(Data $decoratedData): Data
    {
        $suggestions = $this->fetchSuggestions();

        return new SuggestDataDecorator($decoratedData, $suggestions);
    }
}
