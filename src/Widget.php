<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget as BaseWidget;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\ContaoUtil;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_values;
use function call_user_func;
use function count;
use function ctype_digit;
use function explode;
use function implode;
use function is_a;
use function is_array;
use function is_callable;
use function is_scalar;
use function iterator_to_array;
use function max;
use function method_exists;
use function min;
use function ob_end_clean;
use function preg_match;
use function reset;
use function serialize;
use function sprintf;
use function strlen;
use function trim;
use function ucfirst;

use const PHP_INT_MAX;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Widget extends BaseWidget
{
    /**
     * Submit user input
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /** @var Data */
    protected $data;

    /** @var int */
    protected $min = 0;

    /** @var int */
    protected $max = 1;

    /** @var int */
    protected $searchLimit = 20;

    /** @var int */
    protected $suggestLimit = 20;

    /** @var string */
    protected $suggestionsLabel;

    /** @var bool */
    protected $disableBrowsing = false;

    /** @var bool */
    protected $disableSearching = false;

    /** @var bool */
    protected $disableSuggestions = false;

    /** @var bool */
    protected $suggestOnlyEmpty = false;

    /** @var bool */
    protected $contentToggleable = false;

    /** @var array<string,mixed> */
    protected $jsOptions = [];

    /** @var string */
    protected $sort = 'list';

    /** @var string */
    protected $height;

    /** @var string|null */
    protected $table;

    /** @var string|null */
    protected $field;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param array<string,mixed>|bool|null $arrAttributes
     */
    public function __construct($arrAttributes = false)
    {
        parent::__construct($arrAttributes);

        $this->strTemplate = 'hofff_selectri_widget';
        $this->translator  = System::getContainer()->get('translator');
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function __get($key)
    {
        switch ($key) {
            case 'value':
                $value = $this->getValue();
                if ($this->getMaxSelected() === 1) {
                    if (! count($value)) {
                        return null;
                    }

                    $value = reset($value);

                    return $this->canonical ? $value : $value['_key'];
                }

                if ($this->findInSet) {
                    return implode(',', array_keys($value));
                }

                if ($this->canonical) {
                    return serialize($value);
                }

                return array_keys($value);

            case 'table':
            case 'strTable':
                return $this->table;

            case 'field':
            case 'strField':
                return $this->field;

            case 'mandatory':
                return $this->getMinSelected() > 0;

            case 'multiple':
                return $this->getMaxSelected() > 1;

            default:
                return parent::__get($key);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function __set(string $key, $value): void
    {
        switch ($key) {
            case 'value':
                // convert previous values stored as an array
                $value = StringUtil::deserialize($value);
                if (! is_array($value)) {
                    $value = $this->findInSet ? explode(',', $value) : (array) $value;
                }

                $converted = [];
                if ($this->canonical) {
                    foreach ($value as $key => $row) {
                        if (! is_array($row)) {
                            $converted[$row] = ['_key' => $row];
                        } else {
                            isset($row['_key']) ? $key = $row['_key'] : $row['_key'] = $key;
                            $converted[$key]           = $row;
                        }
                    }
                } else {
                    foreach ($value as $key) {
                        $converted[$key] = ['_key' => $key];
                    }
                }

                $this->setValue($converted);
                break;

            case 'table':
            case 'strTable':
                $this->table = $value;
                break;

            case 'field':
            case 'strField':
                $this->field = $value;
                break;

            case 'mandatory':
                if (! $value) {
                    $this->setMinSelected(0);
                } elseif ($this->getMinSelected() === 0) {
                    $this->setMinSelected(1);
                }

                break;

            case 'multiple':
                if (! $value) {
                    $this->setMaxSelected(1);
                } elseif ($this->getMaxSelected() === 1) {
                    $this->setMaxSelected(PHP_INT_MAX);
                }

                break;

            default:
                parent::__set($key, $value);
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addAttributes($arrAttributes): void
    {
        if (! is_array($arrAttributes)) {
            return;
        }

        $this->createDataFromAttributes($arrAttributes);
        unset($arrAttributes['data']);
        unset($arrAttributes['dataFactory']);

        foreach (
            [
                'mandatory'             => false,
                'multiple'              => false,
                'height'                => 'setHeight',
                'sort'                  => 'setSort',
                'min'                   => 'setMinSelected',
                'max'                   => 'setMaxSelected',
                'searchLimit'           => 'setSearchLimit',
                'suggestLimit'          => 'setSuggestLimit',
                'suggestionsLabel'      => 'setSuggestionsLabel',
                'jsOptions'             => 'setJSOptions',
                'disableBrowsing'       => 'setDisableBrowsing',
                'disableSearching'      => 'setDisableSearching',
                'disableSuggestions'    => 'setDisableSuggestions',
                'suggestOnlyEmpty'      => 'setSuggestOnlyEmpty',
                'contentToggleable'     => 'setContentToggleable',
            ] as $key => $method
        ) {
            if (! isset($arrAttributes[$key])) {
                continue;
            }

            if ($method) {
                $this->$method($arrAttributes[$key]);
            } else {
                $this->$key = $arrAttributes[$key];
            }

            unset($arrAttributes[$key]);
        }

        parent::addAttributes($arrAttributes);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate(): void
    {
        $name  = $this->name;
        $match = null;
        if (preg_match('@^([a-z_][a-z0-9_-]*)((?:\[[^\]]+\])+)$@i', $name, $match)) {
            $name = $match[1];
            $path = explode('][', trim($match[2], '[]'));
        }

        $values = Input::postRaw($name);
        if ($path) {
            for ($i = 0, $n = count($path); $i < $n; $i++) {
                if (! is_array($values)) {
                    unset($values);
                    break;
                }

                $values = $values[$path[$i]];
            }
        }

        $values    = (array) $values;
        $selection = $this->getData()->filter((array) $values['selected']);

        if (count($selection) < $this->getMinSelected()) {
            if ($this->getMinSelected() > 1) {
                $this->addError($this->translate('err_min', [$this->label, $this->getMinSelected()]));
            } else {
                $this->addError($this->translate('ERR.mandatory', [$this->label], 'contao_default'));
            }
        } elseif (count($selection) > $this->getMaxSelected()) {
            $this->addError($this->translate('err_max', [$this->label, $this->getMaxSelected()]));
        }

        $selection = array_combine($selection, $selection);
        foreach ($selection as $key => &$data) {
            $data         = (array) $values['data'][$key];
            $data['_key'] = $key;
        }

        unset($data);

        $this->hasErrors() && $this->class = 'error';
        $this->setValue($selection);
        $this->blnSubmitInput = true;
    }

    public function parse(?array $arrAttributes = null): string
    {
        System::loadLanguageFile('hofff_selectri');

        if (! is_array($arrAttributes) || empty($arrAttributes['noAjax'])) {
            $this->generateAjax();
        }

        $this->checkData();

        return parent::parse($arrAttributes);
    }

    public function generate(): string
    {
        return $this->parse(['noAjax' => true]);
    }

    public function generateAjax(): void
    {
        if (Input::get('hofff_selectri_field') !== $this->strId) {
            return;
        }

        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedWhile
        while (ob_end_clean()) {
        }

        $this->checkData();

        $action   = Input::get('hofff_selectri_action');
        $method   = 'generateAjax' . ucfirst($action);
        $response = method_exists($this, $method) ? $this->$method() : null;

        if ($response === null) {
            throw new BadRequestHttpException();
        }

        throw new ResponseException(new JsonResponse($response));
    }

    /**
     * @return array<string,mixed>|null
     */
    public function generateAjaxLevels(): ?array
    {
        if (! $this->getData()->isBrowsable()) {
            return null;
        }

        $key                 = Input::post('hofff_selectri_key');
        strlen($key) || $key = null;

        [$nodes, $start] = $this->getData()->browseFrom($key);
        $nodes           = iterator_to_array($nodes);

        $response                                     = [];
        $response['action']                           = 'levels';
        $response['key']                              = $key;
        $response['empty']                            = ! $nodes;
        $response['empty'] && $response['messages'][] = $this->translate('no_options');
        $this->renderLevels($response, $nodes, $start);

        return $response;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function generateAjaxPath(): ?array
    {
        if (! $this->getData()->isBrowsable()) {
            return null;
        }

        $key = Input::post('hofff_selectri_key');
        if (! strlen($key)) {
            return null;
        }

        $nodes = $this->getData()->browseTo($key);
        $nodes = iterator_to_array($nodes);

        $response                                     = [];
        $response['action']                           = 'path';
        $response['key']                              = $key;
        $response['empty']                            = ! $nodes;
        $response['empty'] && $response['messages'][] = $this->translate('no_options');
        $this->renderLevels($response, $nodes, null);

        return $response;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function generateAjaxToggle(): ?array
    {
        if (! $this->getData()->isBrowsable()) {
            return null;
        }

        $key = Input::post('hofff_selectri_key');
        if (! strlen($key)) {
            return null;
        }

        $open = (bool) Input::post('hofff_selectri_open');

        $unfolded = $this->getUnfolded();
        if ($open) {
            $unfolded[] = $key;
        } else {
            $unfolded = array_diff($unfolded, [$key]);
        }

        $this->setUnfolded($unfolded);

        $response           = [];
        $response['action'] = 'toggle';
        $response['key']    = $key;
        $response['open']   = $open;

        return $response;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function generateAjaxSearch(): ?array
    {
        if (! $this->getData()->isSearchable()) {
            return null;
        }

        $search = Input::post('hofff_selectri_search');
        if (! strlen($search)) {
            return null;
        }

        $nodes = $this->getData()->search($search, $this->getSearchLimit());
        $nodes = iterator_to_array($nodes);
        $nodes = array_filter($nodes, static function (Node $node) {
            return $node->isSelectable();
        });

        $response           = [];
        $response['action'] = 'search';
        $response['search'] = $search;
        if ($nodes) {
            $response['result'] = ContaoUtil::renderTemplate('hofff_selectri_node_list', [
                'widget'    => $this,
                'nodes'     => $nodes,
            ]);
        } else {
            $response['messages'][] = $this->translate('search_empty', [$search]);
        }

        return $response;
    }

    /**
     * @param array<string,mixed> $response
     * @param array<Node>         $nodes
     */
    protected function renderLevels(array &$response, array $nodes, ?string $key): void
    {
        if (! $nodes) {
            return;
        }

        $content = ContaoUtil::renderTemplate('hofff_selectri_node_list', [
            'widget'    => $this,
            'nodes'     => $nodes,
            'children'  => true,
        ]);

        if ($key === null) {
            $response['first'] = $content;
        } else {
            isset($response['start']) || $response['start'] = $key;
            $response['levels'][$key]                       = $content;
        }

        foreach ($nodes as $node) {
            $node->isOpen() && $this->renderLevels(
                $response,
                iterator_to_array($node->getChildrenIterator()),
                $node->getKey()
            );
        }
    }

    public function getData(): Data
    {
        return $this->data;
    }

    public function setData(Data $data): void
    {
        $this->data = $data;
    }

    /**
     * @param array<string,mixed> $attrs
     *
     * @throws SelectriException
     */
    protected function createDataFromAttributes(array $attrs): void
    {
        if (isset($attrs['dataFactory'])) {
            $factory = $attrs['dataFactory'];
        } elseif (isset($attrs['data'])) {
            $factory = $attrs['data'];
        } else {
            return;
        }

        is_callable($factory) && $factory = call_user_func($factory, $this, $attrs);

        if (is_scalar($factory) && is_a($factory, DataFactory::class, true)) {
            $factory = new $factory();
            $factory->setParameters($attrs);
        } elseif (! $factory instanceof DataFactory) {
            throw new SelectriException('invalid selectri data factory configuration');
        }

        $data = $factory->createData($this);
        $this->setData($data);
    }

    /**
     * @throws SelectriException
     */
    protected function checkData(): void
    {
        $data = $this->getData();
        if (! $data) {
            throw new SelectriException('no selectri data set');
        }

        $data->validate();
    }

    /**
     * @return array<string, array<array-key,mixed>>
     */
    public function getValue(): array
    {
        return $this->varValue;
    }

    /**
     * @param array<string, array<array-key,mixed>> $value
     */
    public function setValue(array $value): void
    {
        $this->varValue = $value;
    }

    /**
     * @return array<Node>
     */
    public function getSelectedNodes(): array
    {
        $selection = $this->getData()->getNodes(array_keys($this->getValue()));

        return iterator_to_array($selection);
    }

    /**
     * @return array<Node>
     */
    public function getSuggestedNodes(): array
    {
        if ($this->isDisableSuggestions() || ! $this->getData()->hasSuggestions()) {
            return [];
        }

        if ($this->isSuggestOnlyEmpty() && $this->varValue) {
            return [];
        }

        $suggestions = $this->getData()->suggest($this->getSuggestLimit());

        return iterator_to_array($suggestions);
    }

    public function isOpen(): bool
    {
        return $this->isBrowsable() && $this->mandatory && ! $this->varValue;
    }

    public function getInputName(): string
    {
        return $this->name . '[selected][]';
    }

    public function getAdditionalInputBaseName(): string
    {
        return $this->name . '[data]';
    }

    public function getHeight(): string
    {
        if (! strlen($this->height)) {
            return 'auto';
        }

        if (ctype_digit($this->height)) {
            return $this->height . 'px';
        }

        return $this->height;
    }

    /**
     * @param string $height
     */
    public function setHeight($height): void
    {
        $this->height = $height;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    /**
     * @param string $sort
     *
     * @throws SelectriException
     */
    public function setSort($sort): void
    {
        if ($sort === 'tree') {
            throw new SelectriException('tree sortable not implemented');
        }

        if ($sort === true || $sort === 'list') {
            $this->sort = 'list';
        } else {
            $this->sort = 'preorder';
        }
    }

    public function getMinSelected(): int
    {
        return min($this->min, $this->getMaxSelected());
    }

    public function setMinSelected(int $min): void
    {
        $this->min = max(0, $min);
    }

    public function getMaxSelected(): int
    {
        return $this->max;
    }

    public function setMaxSelected(int $max): void
    {
        $this->max = max(1, $max);
    }

    /**
     * @return array<string,mixed>
     */
    public function getJSOptions(): array
    {
        return $this->jsOptions;
    }

    /**
     * @param array<string,mixed> $jsOptions
     */
    public function setJSOptions(array $jsOptions): void
    {
        $this->jsOptions = $jsOptions;
    }

    public function getSearchLimit(): int
    {
        return $this->searchLimit;
    }

    public function setSearchLimit(int $limit): void
    {
        $this->searchLimit = max(1, $limit);
    }

    public function getSuggestLimit(): int
    {
        return $this->suggestLimit;
    }

    public function setSuggestLimit(int $limit): void
    {
        $this->suggestLimit = max(1, $limit);
    }

    public function getSuggestionsLabel(): string
    {
        return $this->suggestionsLabel !== ''
            ? $this->suggestionsLabel
            : $this->translate('suggestions');
    }

    public function setSuggestionsLabel(string $label): void
    {
        $this->suggestionsLabel = $label;
    }

    public function isBrowsable(): bool
    {
        return $this->data->isBrowsable() && ! $this->isDisableBrowsing();
    }

    public function isDisableBrowsing(): bool
    {
        return $this->disableBrowsing;
    }

    public function setDisableBrowsing(bool $disable): void
    {
        $this->disableBrowsing = $disable;
    }

    public function isSearchable(): bool
    {
        return $this->data->isSearchable() && ! $this->isDisableSearching();
    }

    public function isDisableSearching(): bool
    {
        return $this->disableSearching;
    }

    public function setDisableSearching(bool $disable): void
    {
        $this->disableSearching = $disable;
    }

    public function isDisableSuggestions(): bool
    {
        return $this->disableSuggestions;
    }

    public function setDisableSuggestions(bool $disable): void
    {
        $this->disableSuggestions = $disable;
    }

    public function isSuggestOnlyEmpty(): bool
    {
        return $this->suggestOnlyEmpty;
    }

    public function setSuggestOnlyEmpty(bool $only): void
    {
        $this->suggestOnlyEmpty = $only;
    }

    public function isContentToggleable(): bool
    {
        return $this->contentToggleable;
    }

    public function setContentToggleable(bool $toggleable): void
    {
        $this->contentToggleable = $toggleable;
    }

    public function isDataContainerDriven(): bool
    {
        return strlen($this->table) && strlen($this->field);
    }

    public function getDataContainerTable(): ?string
    {
        return $this->table;
    }

    public function getDataContainerField(): ?string
    {
        return $this->field;
    }

    /**
     * @return array<string,mixed>
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getFieldDCA(): array
    {
        if (! $this->isDataContainerDriven()) {
            return [];
        }

        if (! isset($GLOBALS['TL_DCA'][$this->getDataContainerTable()]['fields'][$this->getDataContainerField()])) {
            throw new RuntimeException(
                sprintf(
                    'Field %s of %s does not exist',
                    $this->getDataContainerField(),
                    $this->getDataContainerTable()
                )
            );
        }

        return $GLOBALS['TL_DCA'][$this->getDataContainerTable()]['fields'][$this->getDataContainerField()];
    }

    /**
     * @return array<int,mixed>
     */
    public function getUnfolded(): array
    {
        if (! $this->isDataContainerDriven()) {
            return [];
        }

        $unfolded = (array) $this->Session->get($this->getSessionKey());
        $unfolded = array_keys($unfolded);
        $unfolded = array_map('strval', $unfolded);

        return $unfolded;
    }

    /**
     * @param array<int,mixed> $unfolded
     */
    public function setUnfolded(array $unfolded): void
    {
        if (! $this->isDataContainerDriven()) {
            return;
        }

        $unfolded = array_values($unfolded);
        $unfolded = array_map('strval', $unfolded);
        $unfolded = array_flip($unfolded);

        $this->Session->set($this->getSessionKey(), $unfolded);
    }

    public function getSessionKey(): string
    {
        return sprintf(
            'hofff_selectri$%s$%s',
            $this->getDataContainerTable(),
            $this->getDataContainerField()
        );
    }

    /** @param list<mixed> $params */
    private function translate(string $key, array $params = [], ?string $domain = null): string
    {
        if ($domain === null) {
            $key    = 'hofff_selectri.' . $key;
            $domain = 'contao_hofff_selectri';
        }

        return $this->translator->trans($key, $params, $domain);
    }
}
