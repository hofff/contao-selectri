<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Util;

use Contao\StringUtil;
use Hofff\Contao\Selectri\Model\Node;

use function vsprintf;

class LabelFormatter
{
    /** @var string */
    private $format;

    /** @var list<string> */
    private $fields;

    /** @var bool */
    private $htmlOutput;

    /**
     * @param list<string> $fields
     */
    public function __construct(string $format, ?array $fields = null, bool $htmlOutput = false)
    {
        $this->setFormat($format);
        $this->setFields((array) $fields);
        $this->setHTMLOutput($htmlOutput);
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array<string> $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function isHTMLOutput(): bool
    {
        return $this->htmlOutput;
    }

    public function setHTMLOutput(bool $htmlOutput): void
    {
        $this->htmlOutput = (bool) $htmlOutput;
    }

    public function getCallback(): callable
    {
        return [$this, 'format'];
    }

    public function format(Node $node): string
    {
        $data   = $node->getData();
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $fields[$field] = $data[$field];
        }

        $label = vsprintf($this->getFormat(), $fields);

        return $this->isHTMLOutput() ? $label : StringUtil::specialchars($label);
    }
}
