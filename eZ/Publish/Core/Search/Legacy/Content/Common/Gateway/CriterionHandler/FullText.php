<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use eZ\Publish\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use eZ\Publish\Core\Persistence\TransformationProcessor;

/**
 * Full text criterion handler.
 */
class FullText extends CriterionHandler
{
    /**
     * Full text search configuration options.
     *
     * @var array
     */
    protected $configuration = [
        // @see getStopWordThresholdValue()
        'stopWordThresholdFactor' => 0.66,
        'enableWildcards' => true,
        'commands' => [
            'apostrophe_normalize',
            'apostrophe_to_doublequote',
            'ascii_lowercase',
            'ascii_search_cleanup',
            'cyrillic_diacritical',
            'cyrillic_lowercase',
            'cyrillic_search_cleanup',
            'cyrillic_transliterate_ascii',
            'doublequote_normalize',
            'endline_search_normalize',
            'greek_diacritical',
            'greek_lowercase',
            'greek_normalize',
            'greek_transliterate_ascii',
            'hebrew_transliterate_ascii',
            'hyphen_normalize',
            'inverted_to_normal',
            'latin1_diacritical',
            'latin1_lowercase',
            'latin1_transliterate_ascii',
            'latin-exta_diacritical',
            'latin-exta_lowercase',
            'latin-exta_transliterate_ascii',
            'latin_lowercase',
            'latin_search_cleanup',
            'latin_search_decompose',
            'math_to_ascii',
            'punctuation_normalize',
            'space_normalize',
            'special_decompose',
            'specialwords_search_normalize',
            'tab_search_normalize',
        ],
    ];

    /**
     * @var int|null
     *
     * @see getStopWordThresholdValue()
     */
    private $stopWordThresholdValue;

    /**
     * Transformation processor to normalize search strings.
     *
     * @var \eZ\Publish\Core\Persistence\TransformationProcessor
     */
    protected $processor;

    /** @var \eZ\Publish\Core\Persistence\Legacy\Content\Language\MaskGenerator */
    private $languageMaskGenerator;

    /**
     * @param array $configuration
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException On invalid $configuration values
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(
        Connection $connection,
        TransformationProcessor $processor,
        MaskGenerator $languageMaskGenerator,
        array $configuration = []
    ) {
        parent::__construct($connection);

        $this->configuration = $configuration + $this->configuration;
        $this->processor = $processor;

        if (
            $this->configuration['stopWordThresholdFactor'] < 0 ||
            $this->configuration['stopWordThresholdFactor'] > 1
        ) {
            throw new InvalidArgumentException(
                "\$configuration['stopWordThresholdFactor']",
                'Stop Word Threshold Factor needs to be between 0 and 1, got: ' . $this->configuration['stopWordThresholdFactor']
            );
        }
        $this->languageMaskGenerator = $languageMaskGenerator;
    }

    /**
     * Check if this criterion handler accepts to handle the given criterion.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     *
     * @return bool
     */
    public function accept(Criterion $criterion)
    {
        return $criterion instanceof Criterion\FullText;
    }

    /**
     * Tokenize String.
     *
     * @param string $string
     *
     * @return array
     */
    protected function tokenizeString($string)
    {
        return preg_split('/[^\w|*]/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get single word query expression.
     *
     * Depending on the configuration of the full text search criterion
     * converter wildcards are either transformed into the respective LIKE
     * queries, or everything is just compared using equal.
     */
    protected function getWordExpression(QueryBuilder $query, string $token): string
    {
        if ($this->configuration['enableWildcards'] && $token[0] === '*') {
            return $query->expr()->like(
                'word',
                $query->createNamedParameter('%' . substr($token, 1))
            );
        }

        if ($this->configuration['enableWildcards'] && $token[strlen($token) - 1] === '*') {
            return $query->expr()->like(
                'word',
                $query->createNamedParameter(substr($token, 0, -1) . '%')
            );
        }

        return $query->expr()->eq('word', $query->createNamedParameter($token));
    }

    /**
     * Get sub query to select relevant word IDs.
     *
     * @uses getStopWordThresholdValue To get threshold for words we would like to ignore in query.
     */
    protected function getWordIdSubquery(QueryBuilder $query, string $string): string
    {
        $subQuery = $this->connection->createQueryBuilder();
        $tokens = $this->tokenizeString(
            $this->processor->transform($string, $this->configuration['commands'])
        );
        $wordExpressions = [];
        foreach ($tokens as $token) {
            $wordExpressions[] = $this->getWordExpression($query, $token);
        }

        // Search for provided string itself as well
        $wordExpressions[] = $this->getWordExpression($query, $string);

        $whereCondition = $subQuery->expr()->orX(...$wordExpressions);

        // If stop word threshold is below 100%, make it part of $whereCondition
        if ($this->configuration['stopWordThresholdFactor'] < 1) {
            $whereCondition = $subQuery->expr()->andX(
                $whereCondition,
                $subQuery->expr()->lt(
                    'object_count',
                    $query->createNamedParameter($this->getStopWordThresholdValue(), ParameterType::STRING)
                )
            );
        }

        $subQuery
            ->select('id')
            ->from('ezsearch_word')
            ->where($whereCondition);

        return $subQuery->getSQL();
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subSelect = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                'ezsearch_object_word_link'
            )->where(
                $expr->in(
                    'word_id',
                    // pass main Query Builder to set query parameters
                    $this->getWordIdSubquery($queryBuilder, $criterion->value)
                )
            );

        if (!empty($languageSettings['languages'])) {
            $languageMask = $this->languageMaskGenerator->generateLanguageMaskFromLanguageCodes(
                $languageSettings['languages'],
                $languageSettings['useAlwaysAvailable'] ?? true
            );

            $subSelect->andWhere(
                $expr->gt(
                    $this->dbPlatform->getBitAndComparisonExpression(
                        'ezsearch_object_word_link.language_mask',
                        $queryBuilder->createNamedParameter($languageMask, ParameterType::INTEGER)
                    ),
                    $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            );
        }

        return $expr->in(
            'c.id',
            $subSelect->getSQL()
        );
    }

    /**
     * Returns an exact content object count threshold to ignore common terms on.
     *
     * Common terms will be skipped if used in more then a given percentage of the total amount of content
     * objects in the database. Percentage is defined by stopWordThresholdFactor configuration.
     *
     * Example: If stopWordThresholdFactor is 0.66 (66%), and a term like "the" exists in more then 66% of the content, it
     *          will ignore the phrase as it is assumed to not add any value ot the search.
     *
     * Caches the result for the instance used as we don't need this to be super accurate as it is based on percentage,
     * set by stopWordThresholdFactor.
     *
     * @return int
     */
    protected function getStopWordThresholdValue(): int
    {
        if ($this->stopWordThresholdValue !== null) {
            return $this->stopWordThresholdValue;
        }

        // Cached value does not exists, do a simple count query on ezcontentobject table
        $query = $this->connection->createQueryBuilder();
        $query
            ->select($this->dbPlatform->getCountExpression('id'))
            ->from(ContentGateway::CONTENT_ITEM_TABLE);

        $count = (int)$query->execute()->fetchColumn();

        // Calculate the int stopWordThresholdValue based on count (first column) * factor
        return $this->stopWordThresholdValue = (int)($count * $this->configuration['stopWordThresholdFactor']);
    }
}
