<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

use function PHPUnit\Framework\isBool;

/**
 * Typo parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#typo-parameters
 */
class Typo implements Arrayable
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
     * Maximum number of typographical errors (0, 1 or 2) that would be tolerated.
     * Damerau–Levenshtein distance (opens new window)is used to calculate the number of errors.
     * You can also control num_typos on a per field basis. For example, if you are querying 3 fields and want to disable typo tolerance on the first field, use ?num_typos=0,1,1. The order should match the order of fields in query_by. If a single value is specified for num_typos the same value is used for all fields specified in query_by.
     */
    protected int|Fields|null $numTypos = null;

    /**
     * Minimum word length for 1-typo correction to be applied. The value of num_typos is still treated as the maximum allowed typos.
     */
    protected ?int $minLen1Typo = null;

    /**
     * Minimum word length for 2-typo correction to be applied. The value of num_typos is still treated as the maximum allowed typos.
     */
    protected ?int $minLen2Typo = null;

    /**
     * Treat space as typo: search for q=basket ball if q=basketball is not found or vice-versa. Splitting/joining of tokens will only be attempted if the original query produces no results. To always trigger this behavior, set value to always. To disable, set value to off.
     */
    protected ?string $splitJoinTokens = null;

    /**
     * If typo_tokens_threshold is set to a number N, if at least N results are not found for a search term, then Typesense will start looking for typo-corrected variations, until at least N results are found, up to a maximum of num_typo number of corrections. Set typo_tokens_threshold to 0 to disable typo tolerance.
     */
    protected ?int $typoTokensThreshold = null;

    /**
     * If drop_tokens_threshold is set to a number N and a search query contains multiple words (eg: wordA wordB), if at least N results with both wordA and wordB in the same document are not found, then Typesense will drop wordB and search for documents with just wordA. Typesense will keep dropping keywords like this left to right and/or right to left, until at least N documents are found. Words that have the least individual results are dropped first. Set drop_tokens_threshold to 0 to disable dropping of words (tokens).
     */
    protected ?int $dropTokensThreshold = null;

    /**
     * Dictates the direction in which the words in the query must be dropped when the original words in the query do not appear in any document.'
     * Values: right_to_left (default), left_to_right, both_sides:3
     */
    protected ?string $dropTokensMode = null;

    /**
     * Set this parameter to false to disable typos on numerical query tokens. Default: true.
     */
    protected ?bool $enableTyposForNumericalTokens = null;

    /**
     * Set this parameter to false to disable typos on alphanumerical query tokens. Default: true.
     */
    protected ?bool $enableTyposForAlphaNumericalTokens = null;

    /**
     * Allow synonym resolution on typo-corrected words in the query.
     * If set to 0, synonym resolution will be disabled on typo-corrected words.
     *
     * @default 0
     *
     * @available: v0.27.0
     */
    protected ?int $synonymNumTypos = null;

    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        ?int $numTypos = null,
        ?int $minLen1Typo = null,
        ?int $minLen2Typo = null,
        ?string $splitJoinTokens = null,
        ?int $typoTokensThreshold = null,
        ?int $dropTokensThreshold = null,
        ?string $dropTokensMode = null,
        ?bool $enableTyposForNumericalTokens = null,
        ?bool $enableTyposForAlphaNumericalTokens = null,
        ?int $synonymNumTypos = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->numTypos = $numTypos ?? $this->numTypos;
        $this->minLen1Typo = $minLen1Typo ?? $this->minLen1Typo;
        $this->minLen2Typo = $minLen2Typo ?? $this->minLen2Typo;
        $this->splitJoinTokens = $splitJoinTokens ?? $this->splitJoinTokens;
        $this->typoTokensThreshold = $typoTokensThreshold ?? $this->typoTokensThreshold;
        $this->dropTokensThreshold = $dropTokensThreshold ?? $this->dropTokensThreshold;
        $this->dropTokensMode = $dropTokensMode ?? $this->dropTokensMode;
        $this->enableTyposForNumericalTokens = $enableTyposForNumericalTokens ?? $this->enableTyposForNumericalTokens;
        $this->enableTyposForAlphaNumericalTokens = $enableTyposForAlphaNumericalTokens ?? $this->enableTyposForAlphaNumericalTokens;
        $this->synonymNumTypos = $synonymNumTypos ?? $this->synonymNumTypos;
    }

    /**
     * typos
     * Maximum number of typographical errors (0, 1 or 2) that would be tolerated.
     *
     * @default 2
     */
    public function typos(int|array $numTypos = 2): static
    {
        // if array, then it should be per field basis
        if (is_array($numTypos)) {
            // / $this->parameters->query
        }
        // may be 0,1 or 2
        if (! in_array($numTypos, [0, 1, 2])) {
            throw new \InvalidArgumentException('Invalid numTypos');
        }
        $this->numTypos = $numTypos;

        return $this;
    }

    /**
     * minLen1Typo
     * Minimum word length for 1-typo correction to be applied. The value of num_typos is still treated as the maximum allowed typos.
     */
    public function minLen1Typo(int $minLen1Typo = 4): static
    {
        $this->minLen1Typo = $minLen1Typo;

        return $this;
    }

    /**
     * minLen2Typo
     * Minimum word length for 2-typo correction to be applied. The value of num_typos is still treated as the maximum allowed typos.
     */
    public function minLen2Typo(int $minLen2Typo = 7): static
    {
        $this->minLen2Typo = $minLen2Typo;

        return $this;
    }

    /**
     * splitJoinTokens
     * Treat space as typo: search for q=basket ball if q=basketball is not found or vice-versa. Splitting/joining of tokens will only be attempted if the original query produces no results. To always trigger this behavior, set value to always. To disable, set value to off.
     */
    public function splitJoinTokens(string|bool $splitJoinTokens = 'fallback'): static
    {
        // should be one of always, off, fallback
        $splitJoinTokens = [
            'always',
            'off',
            'fallback',
        ];
        if (! isBool($splitJoinTokens) || ! in_array($splitJoinTokens, $splitJoinTokens)) {
            throw new \InvalidArgumentException('Invalid splitJoinTokens');
        }
        if (is_bool($splitJoinTokens)) {
            $splitJoinTokens = $splitJoinTokens ? 'always' : 'off';
        }

        $this->splitJoinTokens = $splitJoinTokens;

        return $this;
    }

    /**
     * mode: drop tokens mode
     * Dictates the direction in which the words in the query must be dropped when the original words in the query do not appear in any document.'
     * Values: rtl, ltr, both
     *
     * @default rtl
     */
    public function mode(string $mode = 'rtl'): static
    {
        // should be one of rtl: right_to_left, ltr: left_to_right, both: both_sides:3
        $modes = [
            'rtl' => 'right_to_left',
            'ltr' => 'left_to_right',
            'both' => 'both_sides:3',
        ];
        if (! array_key_exists($mode, $modes) || ! in_array($mode, $modes)) {
            throw new \InvalidArgumentException('Invalid mode');
        }
        $this->dropTokensMode = in_array($mode, $modes) ? $mode : $modes[$mode];

        return $this;
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return array_filter([
            'num_typos' => $this->numTypos instanceof Fields ? $this->numTypos->get('typos') : $this->numTypos,
            'min_len_1typo' => $this->minLen1Typo,
            'min_len_2typo' => $this->minLen2Typo,
            'split_join_tokens' => $this->splitJoinTokens,
            'typo_tokens_threshold' => $this->typoTokensThreshold,
            'drop_tokens_threshold' => $this->dropTokensThreshold,
            'drop_tokens_mode' => $this->dropTokensMode,
            'enable_typos_for_numerical_tokens' => $this->enableTyposForNumericalTokens,
            'enable_typos_for_alpha_numerical_tokens' => $this->enableTyposForAlphaNumericalTokens,
            'synonym_num_typos' => $this->synonymNumTypos,
        ], fn ($value) => ! is_null($value));
    }
}
