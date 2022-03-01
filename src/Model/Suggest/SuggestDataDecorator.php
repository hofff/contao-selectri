<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Suggest;

use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataDelegate;
use Iterator;

use function array_slice;
use function count;

class SuggestDataDecorator extends DataDelegate
{
    /** @var list<string> */
    private $suggestions;

    /**
     * @param list<string> $suggestions
     */
    public function __construct(Data $delegate, ?array $suggestions = null)
    {
        parent::__construct($delegate);

        $this->suggestions = (array) $suggestions;
    }

    /**
     * @return list<string>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function hasSuggestions(): bool
    {
        return count($this->suggestions) > 0;
    }

    /** {@inheritDoc} */
    public function suggest(int $limit, int $offset = 0): Iterator
    {
        $keys = array_slice($this->suggestions, $offset, $limit);

        return $this->getNodes($keys);
    }
}
