<?php
/**
 * OsmApi class.
 */

namespace OpenVegeMap\Editor;

use FluidXml\FluidXml;
use GeoJson\Feature\Feature;
use GeoJson\Geometry\Point;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Manage calls to the various OpenStreetMap APIs.
 */
class OsmApi
{
    /**
     * Guzzle HTTP client.
     *
     * @var Client
     */
    private $client;

    /**
     * Main OSM API URL.
     *
     * @var string
     */
    private $apiUrl;

    /**
     * OSM tags that can be edited.
     *
     * @var string[]
     */
    const ALLOWED_TAGS = ['diet:vegan', 'diet:vegetarian'];

    /**
     * OsmApi constructor.
     *
     * @param string $apiUrl Main OSM API URL
     */
    public function __construct($apiUrl = 'https://api.openstreetmap.org/api/0.6/')
    {
        $this->client = new Client();
        $this->apiUrl = $apiUrl;
    }

    /**
     * Get OSM node by ID.
     *
     * @param string $type OSM type (node or way)
     * @param int $id OSM node ID
     *
     * @return Feature OSM node
     * @throws GuzzleException
     */
    public function getById(string $type, int $id): Feature
    {
        $suffix = '';
        if ($type == 'way') {
            $suffix = '/full';
        }
        $result = $this->client->request(
            'GET',
            $this->apiUrl . $type . '/' . $id . $suffix,
            [
                'auth' => [OSM_USER, OSM_PASS],
            ]
        );
        $xml = new FluidXml(null);
        $xml->addChild($result->getBody()->getContents());
        $tags = [];
        foreach ($xml->query('tag') as $tag) {
            $tags[$tag->getAttribute('k')] = $tag->getAttribute('v');
        }
        $node = $xml->query($type);
        if ($type == 'way') {
            $subnodes = [];
            foreach ($xml->query('node') as $subnode) {
                $subnodes[] = new Point([(float)$subnode->getAttribute('lon'), (float)$subnode->getAttribute('lat')]);
            }
            $way = new MultiPoint($subnodes);
            $coords = $way->getCenter();
        } else {
            $coords = new Point([(float)$node[0]->getAttribute('lon'), (float)$node[0]->getAttribute('lat')]);
        }

        if (isset($tags['website']) && empty(parse_url($tags['website'], PHP_URL_SCHEME))) {
            $tags['website'] = 'http://' . $tags['website'];
        }

        return new Feature(
            $coords,
            $tags,
            $node[0]->getAttribute('id')
        );
    }

    /**
     * Get new OSM changeset ID.
     *
     * @return int Changeset ID
     * @throws GuzzleException
     */
    private function getChangeset(): int
    {
        $osm = new FluidXml('osm');
        $osm->add('changeset');
        $changeset = $osm->query('changeset');
        $changeset->add('tag', null, ['k' => 'comment', 'v' => 'Edited from openvegemap.netlib.re']);
        $changeset->add('tag', null, ['k' => 'created_by', 'v' => 'OpenVegeMap']);

        $result = $this->client->request(
            'PUT',
            $this->apiUrl . 'changeset/create',
            [
                'auth' => [OSM_USER, OSM_PASS],
                'body' => $osm,
            ]
        );

        return (int)$result->getBody()->getContents();
    }

    /**
     * Update an OSM node with new tag values.
     *
     * @param string $type OSM type (node or way)
     * @param int $id OSM node ID
     * @param array $tags Tags
     *
     * @return void
     * @throws GuzzleException
     */
    public function updateNode(string $type, int $id, array $tags)
    {
        $baseXml = $this->client->request(
            'GET',
            $this->apiUrl . $type . '/' . $id,
            [
                'auth' => [OSM_USER, OSM_PASS],
            ]
        )->getBody()->getContents();

        $xml = new FluidXml(null);
        $xml->addChild($baseXml);
        $node = $xml->query($type);
        $node->attr('changeset', $this->getChangeset());
        $node->attr('timestamp', date('c'));
        if (in_array($tags['diet:vegan'], ['only', 'yes'])) {
            $tags['diet:vegetarian'] = 'yes';
        }
        foreach ($tags as $key => $value) {
            if (!empty($value) && in_array($key, self::ALLOWED_TAGS)) {
                $tag = $node->query('tag[k="' . $key . '"]');
                if ($tag->size() > 0) {
                    $tag->attr('v', $value);
                } else {
                    $node->add('tag', null, ['k' => $key, 'v' => $value]);
                }
            }
        }

        $this->client->request(
            'PUT',
            $this->apiUrl . $type . '/' . $id,
            [
                'auth' => [OSM_USER, OSM_PASS],
                'body' => $xml,
            ]
        );
    }
}
