<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Search;

use EasyCorp\Bundle\EasyAdminBundle\Search\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public function createSearchQueryBuilder(array $entityConfig, $searchQuery, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        // Hack to get (private) doctrine from parent.
        $property = new \ReflectionProperty(BaseQueryBuilder::class, 'doctrine');
        $property->setAccessible(true);
        $doctrine = $property->getValue($this);

        // @var EntityManager
        $em = $doctrine->getManagerForClass($entityConfig['class']);
        // @var DoctrineQueryBuilder
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($entityConfig['class'], 'entity')
        ;

        $isSearchQueryNumeric = is_numeric($searchQuery);
        $isSearchQuerySmallInteger = (\is_int($searchQuery) || ctype_digit($searchQuery)) && $searchQuery >= -32768 && $searchQuery <= 32767;
        $isSearchQueryInteger = (\is_int($searchQuery) || ctype_digit($searchQuery)) && $searchQuery >= -2147483648 && $searchQuery <= 2147483647;
        $isSearchQueryUuid = 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $searchQuery);
        $lowerSearchQuery = mb_strtolower($searchQuery);

        $queryParameters = [];
        $entitiesAlreadyJoined = [];
        foreach ($entityConfig['search']['fields'] as $fieldName => $metadata) {
            $entityName = 'entity';
            if (false !== strpos($fieldName, '.')) {
                list($associatedEntityName, $associatedFieldName) = explode('.', $fieldName);
                if (!\in_array($associatedEntityName, $entitiesAlreadyJoined, true)) {
                    $queryBuilder->leftJoin('entity.'.$associatedEntityName, $associatedEntityName);
                    $entitiesAlreadyJoined[] = $associatedEntityName;
                }

                $entityName = $associatedEntityName;
                $fieldName = $associatedFieldName;
            }

            $isSmallIntegerField = 'smallint' === $metadata['dataType'];
            $isIntegerField = 'integer' === $metadata['dataType'];
            $isNumericField = \in_array($metadata['dataType'], ['number', 'bigint', 'decimal', 'float'], true);
            $isTextField = \in_array($metadata['dataType'], ['string', 'text'], true);
            $isGuidField = 'guid' === $metadata['dataType'];

            // this complex condition is needed to avoid issues on PostgreSQL databases
            if (
                ($isSmallIntegerField && $isSearchQuerySmallInteger) ||
                ($isIntegerField && $isSearchQueryInteger) ||
                ($isNumericField && $isSearchQueryNumeric)
            ) {
                $queryBuilder->orWhere(sprintf('%s.%s = :numeric_query', $entityName, $fieldName));
                // adding '0' turns the string into a numeric value
                $queryParameters['numeric_query'] = 0 + $searchQuery;
            } elseif ($isGuidField && $isSearchQueryUuid) {
                $queryBuilder->orWhere(sprintf('%s.%s = :uuid_query', $entityName, $fieldName));
                $queryParameters['uuid_query'] = $searchQuery;
            } elseif ($isTextField) {
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :fuzzy_query', $entityName, $fieldName));
                $queryParameters['fuzzy_query'] = '%'.$lowerSearchQuery.'%';

                $words = [];
                $tokens = preg_split('/\s+/', $searchQuery, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $index => $word) {
                    if (preg_match('/(?P<delimiter>[^[:alnum:][:space:]\\\\])(?P<pattern>.*)(?P=delimiter)(?P<matchtype>.*)/', $word, $matches)) {
                        $parameterName = '_regexp_'.$index;
                        $queryBuilder->orWhere(sprintf('REGEXP(%s.%s, :%s) = 1', $entityName, $fieldName, $parameterName));
                        $queryParameters[$parameterName] = $matches['pattern'];
                    } else {
                        $words[] = $word;
                    }
                }

                if ($words) {
                    $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:words_query)', $entityName, $fieldName));
                    $queryParameters['words_query'] = array_map('mb_strtolower', $words);
                }
            }
        }

        if (0 !== \count($queryParameters)) {
            $queryBuilder->setParameters($queryParameters);
        }

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }

        $isSortedByDoctrineAssociation = false !== strpos($sortField, '.');
        if ($isSortedByDoctrineAssociation) {
            list($associatedEntityName, $associatedFieldName) = explode('.', $sortField);
            if (!\in_array($associatedEntityName, $entitiesAlreadyJoined, true)) {
                $queryBuilder->leftJoin('entity.'.$associatedEntityName, $associatedEntityName);
                $entitiesAlreadyJoined[] = $associatedEntityName;
            }
        }

        if (null !== $sortField) {
            $queryBuilder->orderBy(sprintf('%s%s', $isSortedByDoctrineAssociation ? '' : 'entity.', $sortField), $sortDirection ?: 'DESC');
        }

        return $queryBuilder;
    }
}
