<?php declare(strict_types = 1);

namespace Spot\Application;

use Psr\Http\Message\ServerRequestInterface;

final class FilterService
{
    private static function runFilters(array $input, array $filters) : array
    {
        foreach ($filters as $key => $filter) {
            $value = self::dotnotatedFromArray($key, $input);
            if (!is_null($value) && is_callable($filter)) {
                $newValue = $filter($value);
                if ($newValue !== $value) {
                    self::dotnotatedToArray($key, $newValue, $input);
                }
            }
        }
        return $input;
    }

    private static function dotnotatedFromArray(string $key, array $array): mixed
    {
        $keys = explode('.', $key);
        while ($sub = array_shift($keys)) {
            if (!is_array($array) || !isset($array[$sub])) {
                return null;
            }
            $array = $array[$sub];
        }
        return $array;
    }

    private static function dotnotatedToArray(string $key, mixed $value, array $array): mixed
    {
        $keys = explode('.', $key);
        while ($sub = array_shift($keys)) {
            if (!isset($array[$sub])) {
                return null;
            }
            $array = $array[$sub];
        }
        return $array;
    }

    public static function filter(ServerRequestInterface $request, array $filters): ServerRequestInterface
    {
        return $request->withParsedBody(self::runFilters((array) $request->getParsedBody(), $filters));
    }

    public static function filterQuery(ServerRequestInterface $request, array $filters): ServerRequestInterface
    {
        return $request->withQueryParams(self::runFilters((array) $request->getQueryParams(), $filters));
    }
}
