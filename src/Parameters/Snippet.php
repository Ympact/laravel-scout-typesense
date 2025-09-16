<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

/**
 * Snippet parameters
 */
class Snippet
{
    /**
     * Model
     */
    protected Model $model;

    /**
     * Schema
     */
    protected Schema $schema;

    /**
     * Parameters
     */
    protected Parameters $parameters;

    /**
     * Highlight fields: Comma separated list of fields that should be highlighted with snippetting. You can use this parameter to highlight fields that you don't query for, as well.
     *
     * @default: all queried fields will be highlighted.
     *
     * @available: v0.21.0
     */
    protected ?Fields $highlightFields = null;

    /**
     * Highlight full fields: Comma separated list of fields which should be highlighted fully without snippeting.
     *
     * @default: all fields will be snippeted.
     *
     * @available: v0.21.0
     */
    protected ?Fields $highlightFullFields = null;

    /**
     * Highlight affix num tokens: The number of tokens that should surround the highlighted text on each side. This controls the length of the snippet.
     *
     * @default: 4
     *
     * @available: v0.21.0
     */
    protected ?int $highlightAffixNumTokens = null;

    /**
     * Highlight start tag: The start tag used for the highlighted snippets.
     *
     * @default: <mark>
     *
     * @available: v0.21.0
     */
    protected ?string $highlightStartTag = null;

    /**
     * Highlight end tag: The end tag used for the highlighted snippets.
     *
     * @default: </mark>
     *
     * @available: v0.21.0
     */
    protected ?string $highlightEndTag = null;

    /**
     * Enable highlight v1: Flag for disabling the deprecated, old highlight structure in the response.
     *
     * @default: true
     *
     * @available: v0.21.0
     */
    protected ?bool $enableHighlightV1 = null;

    /**
     * Snippet threshold: Field values under this length will be fully highlighted, instead of showing a snippet of relevant portion.
     *
     * @default: 30
     *
     * @available: v0.21.0
     */
    protected ?int $snippetThreshold = null;

    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        Fields|array|string|null $highlightFields = null,
        Fields|array|string|null $highlightFullFields = null,
        ?int $highlightAffixNumTokens = null,
        ?string $highlightStartTag = null,
        ?string $highlightEndTag = null,
        ?bool $enableHighlightV1 = null,
        ?int $snippetThreshold = null

    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->highlightFields = Fields::from($model, $highlightFields);
        $this->highlightFullFields = Fields::from($model, $highlightFullFields);
        $this->highlightAffixNumTokens = $highlightAffixNumTokens ?? $this->highlightAffixNumTokens;
        $this->highlightStartTag = $highlightStartTag ?? $this->highlightStartTag;
        $this->highlightEndTag = $highlightEndTag ?? $this->highlightEndTag;
        $this->enableHighlightV1 = $enableHighlightV1 ?? $this->enableHighlightV1;
        $this->snippetThreshold = $snippetThreshold ?? $this->snippetThreshold;
    }

    /**
     * Get highlight fields.
     */
    public function getFields(): ?Fields
    {
        return $this->highlightFields;
    }

    /**
     * Set highlight fields.
     *
     * @param  string  $highlightFields
     */
    public function fields(string|Fields|array $highlightFields): static
    {
        $this->highlightFields = Fields::from($this->model, $highlightFields);

        return $this;
    }

    /**
     * Disable snippetting.
     */
    public function disable(): static
    {
        $this->highlightFields = null;
        $this->highlightFullFields = null;

        return $this;
    }

    /**
     * Get highlight full fields.
     */
    public function getFullFields(): ?Fields
    {
        return $this->highlightFullFields;
    }

    /**
     * Set highlight full fields.
     *
     * @param  string  $highlightFullFields
     */
    public function fullFields(string|Fields|array $highlightFullFields): static
    {
        $this->highlightFullFields = Fields::from($this->model, $highlightFullFields);

        return $this;
    }

    /**
     * Get highlight affix num tokens.
     *
     * @return mixed
     */
    public function getAffixNumTokens(): ?int
    {
        return $this->highlightAffixNumTokens;
    }

    /**
     * Set highlight affix num tokens.
     *
     * @param  mixed  $highlightAffixNumTokens
     */
    public function affixNumTokens(?int $highlightAffixNumTokens): static
    {
        if ($highlightAffixNumTokens < 0) {
            throw new \InvalidArgumentException('Highlight affix num tokens must be a positive integer.');
        }
        $this->highlightAffixNumTokens = $highlightAffixNumTokens;

        return $this;
    }

    /**
     * Get highlight start tag.
     */
    public function getStartTag(): ?string
    {
        return $this->highlightStartTag;
    }

    /**
     * Get highlight end tag
     */
    public function getEndTag(): ?string
    {
        return $this->highlightEndTag;
    }

    /**
     * Sets both start and end tag
     *
     * @param  string  $highlightTag  HTML tag such as <mark>
     */
    public function tag(string $highlightTag): static
    {
        if (! str_starts_with($highlightTag, '<') || ! str_ends_with($highlightTag, '>')) {
            throw new \InvalidArgumentException('Highlight tag must be a valid HTML-like tag.');
        }
        $this->highlightStartTag = $highlightTag;
        $this->highlightEndTag = str_replace('<', '</', $highlightTag);

        return $this;
    }

    /**
     * snippetThreshold
     */
    public function getThreshold(): ?int
    {
        return $this->snippetThreshold;
    }

    /**
     * snippetThreshold
     */
    public function threshold(?int $snippetThreshold): static
    {
        if ($snippetThreshold < 0) {
            throw new \InvalidArgumentException('Snippet threshold must be a positive integer.');
        }
        $this->snippetThreshold = $snippetThreshold;

        return $this;
    }

    /**
     * Summary of toArray
     */
    public function toArray(): array
    {
        return array_filter([
            'highlight_fields' => $this->highlightFields->get(), // ?? 'none',
            'highlight_full_fields' => $this->highlightFullFields->get(), // ?? 'none',
            'highlight_affix_num_tokens' => $this->highlightAffixNumTokens,
            'highlight_start_tag' => $this->highlightStartTag,
            'highlight_end_tag' => $this->highlightEndTag,
            'enable_highlight_v1' => $this->enableHighlightV1,
            'snippet_threshold' => $this->snippetThreshold,
        ], fn ($value) => $value !== null);
    }
}
