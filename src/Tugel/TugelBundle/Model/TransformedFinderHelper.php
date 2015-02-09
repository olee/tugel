<?php

namespace Tugel\TugelBundle\Model;

use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\SearchableInterface;
use Elastica\Query;

class TransformedFinderHelper extends TransformedFinder {

	/**
	 * @var TransformedFinder
	 */
	protected $finder;
	
	/**
	 * @var \Elastica\ResultSet
	 */
	public $lastResult;

	public function __construct(TransformedFinder $finder) {
		$this->finder = $finder;
	}

	/**
	 * @return SearchableInterface
	 */
	public function getSearchable() {
		return $this->finder->searchable;
	}

	/**
	 * @return ElasticaToModelTransformerInterface
	 */
	public function getTransformer() {
		return $this->finder->transformer;
	}

	public function findWithScores($query, $minimalMaxScore = 0.01) {
		$query = Query::create($query);

		$this->lastResult = $result = $this->getSearchable()->search($query);
		if ($result->getMaxScore() > $minimalMaxScore)
			$minimalMaxScore = $result->getMaxScore();

		$queryResults = $result->getResults();
		$results = $this->getTransformer()->transform($queryResults);

		foreach ($results as $key => $entity) {
			$hit = $queryResults[$key]->getHit();
			$entity->_score = $hit['_score'];
			$entity->_percentScore = $entity->_score / $minimalMaxScore;
		}

		return $results;
	}

}
