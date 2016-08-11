<?php

namespace GeoIp2\Record;

/**
 *
 * Contains data for the traits record associated with an IP address
 *
 * This record is returned by all the end points.
 *
 * @property int $autonomousSystemNumber The {@link
 * http://en.wikipedia.org/wiki/Autonomous_system_(Internet) autonomous
 * system number} associated with the IP address. This attribute is only
 * available from the City and Insights web service end points.
 *
 * @property string $autonomousSystemOrganization The organization
 * associated with the registered {@link
 * http://en.wikipedia.org/wiki/Autonomous_system_(Internet) autonomous
 * system number} for the IP address. This attribute is only available from
 * the City and Insights web service end points.
 *
 * @property string $domain The second level domain associated with the
 * IP address. This will be something like "example.com" or "example.co.uk",
 * not "foo.example.com". This attribute is only available from the
 * City and Insights web service end points.
 *
 * @property string $ipAddress The IP address that the data in the model
 * is for. If you performed a "me" lookup against the web service, this
 * will be the externally routable IP address for the system the code is
 * running on. If the system is behind a NAT, this may differ from the IP
 * address locally assigned to it. This attribute is returned by all end
 * points.
 *
 * @property boolean $isAnonymousProxy This is true if the IP is an
 * anonymous proxy. See {@link http://dev.maxmind.com/faq/geoip#anonproxy}
 * for further details. This attribute is returned by all end points.
 *
 * @property boolean $isSatelliteProvider This is true if the IP belongs
 * to a satellite Internet provider.  This attribute is returned by all
 * end points.
 *
 * @property string $isp The name of the ISP associated with the IP address.
 * This attribute is only available from the City and Insights web service end
 * points.
 *
 * @property string $organization The name of the organization associated
 * with the IP address. This attribute is only available from the City and
 * Insights web service end points.
 *
 * @property string $userType <p>The user type associated with the IP
 *  address. This can be one of the following values:</p>
 *  <ul>
 *    <li>business
 *    <li>cafe
 *    <li>cellular
 *    <li>college
 *    <li>content_delivery_network
 *    <li>dialup
 *    <li>government
 *    <li>hosting
 *    <li>library
 *    <li>military
 *    <li>residential
 *    <li>router
 *    <li>school
 *    <li>search_engine_spider
 *    <li>traveler
 * </ul>
 * <p>
 *   This attribute is only available from the Insights web service end
 *   point.
 * </p>
 */
class Traits extends AbstractRecord
{
    /**
     * @ignore
     */
    protected $validAttributes = array(
        'autonomousSystemNumber',
        'autonomousSystemOrganization',
        'domain',
        'isAnonymousProxy',
        'isSatelliteProvider',
        'isp',
        'ipAddress',
        'organization',
        'userType'
    );
}
