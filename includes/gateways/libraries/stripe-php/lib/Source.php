<?php

namespace Stripe;

/**
 * Class Source
 *
 * @package Stripe
 */
class Source extends ApiResource
{
    /**
     * @param string $id The ID of the Source to retrieve.
     * @param array|string|null $opts
     *
     * @return Source
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of Sources
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Source The created Source.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }
}
