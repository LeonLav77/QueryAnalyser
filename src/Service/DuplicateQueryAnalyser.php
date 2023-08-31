<?php

namespace LeonLav77\QueryAnalyser\Service;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Http\Kernel;
use LeonLav77\QueryAnalyser\Middleware\QueryTrackerMiddleware;

class DuplicateQueryAnalyser
{
	public static function enableQueryLogging()
	{
		app()->make(Kernel::class)->pushMiddleware(QueryTrackerMiddleware::class);
	}

	public static function analyse(): array
	{
		$queries = DB::getQueryLog();

		$queryArray = self::getQueryLogWithBindings($queries);

		return self::countDuplicateQueries($queryArray);
	}

	private static function getQueryLogWithBindings(array $queries): array
	{
		$queryArray = [];

		foreach ($queries as $queryInfo) {
			$query = $queryInfo['query'];
			$bindings = $queryInfo['bindings'];

			foreach ($bindings as $index => $binding) {
				if (strpos($binding, 'App\Models\\') === 0) {
					$query = self::replaceBindingWithTableName($query, $binding, $index + 1);
				}
			}

			$queryArray[] = $query;
		}

		return $queryArray;
	}

	private static function replaceBindingWithTableName(string $query, string $binding, int $index): string
	{
		return self::str_replace_n('?', $binding, $query, $index);
	}

	private static function countDuplicateQueries(array $queries): array
	{
		$queryCounts = [];
		$similarityThreshold = 0.9;

		foreach ($queries as $query) {
			$found = false;

			foreach ($queryCounts as $storedQuery => $count) {
				$similarity = self::calculateSimilarity($query, $storedQuery);

				if ($similarity >= $similarityThreshold) {
					$queryCounts[$storedQuery]++;
					$found = true;

					break;
				}
			}

			if (!$found) {
				$queryCounts[$query] = 1;
			}
		}

		return $queryCounts;
	}

	private static function calculateSimilarity(string $query, string $storedQuery): float
	{
		return similar_text($query, $storedQuery) / max(strlen($query), strlen($storedQuery));
	}

    private static function str_replace_n(string $search, string $replace, string $subject, int $occurrence): string
    {
        $search = preg_quote($search);

        return preg_replace("/^((?:(?:.*?$search){" . --$occurrence . "}.*?))$search/", "$1$replace", $subject);
    }
}
