<?php
/**
 * OsmApiTest class.
 */

namespace OpenVegeMap\Test;

use GeoJson\Feature\Feature;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use OpenVegeMap\Editor\OsmApi;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OsmApi class.
 */
class OsmApiTest extends TestCase
{
    /**
     * OsmApi instance.
     *
     * @var OsmApi
     */
    private $api;

    /**
     * Setup properties used in multiple tests.
     */
    protected function setUp()
    {
        $this->api = new OsmApi();
    }

    /**
     * Test the getById() function.
     *
     * @param string $type OSM type (node or way)
     * @param int $id OSM element ID
     *
     * @return void
     * @dataProvider nodeProvider
     * @throws GuzzleException
     */
    public function testgetById($type, $id)
    {
        $feature = $this->api->getById($type, $id);
        $this->assertInstanceOf(Feature::class, $feature);
        $this->assertEquals($id, $feature->getId());
    }

    /**
     * Return nodes and ways used in tests.
     *
     * @return array[]
     */
    public function nodeProvider(): array
    {
        return [
            ['node', 4165743782],
            ['way', 39654586],
            ['node', 2001604212],
        ];
    }

    /**
     * Test the updateNode() function.
     *
     * @return void
     */
    public function testUpdateNode()
    {
        $this->markTestIncomplete('We need a way to reliably create a node first.');
    }

    /**
     * Test the updateNode() function with a non-existing node.
     *
     * @return void
     *
     * @throws GuzzleException
     */
    public function testUpdateNodeWithWrongNode()
    {
        $this->expectException(ClientException::class);
        $this->api->updateNode('node', 42, []);
    }
}
