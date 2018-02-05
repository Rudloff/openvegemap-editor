<?php
/**
 * EditorController class.
 */

namespace OpenVegeMap\Editor\Controller;

use Buzz\Browser;
use Buzz\Client\Curl;
use Exception;
use Interop\Container\ContainerInterface;
use Nominatim\Consumer;
use Nominatim\Query;
use OpenVegeMap\Editor\OsmApi;
use Plasticbrain\FlashMessages\FlashMessages;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Smarty;

/**
 * Main controller for the editor.
 */
class MainController
{
    /**
     * Slim container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * OsmApi instance.
     *
     * @var OsmApi
     */
    private $api;

    /**
     * FlashMessages instance.
     *
     * @var FlashMessages
     */
    private $msg;

    /**
     * Smarty view.
     *
     * @var Smarty
     */
    private $view;

    /**
     * Node types the editor is allowed to edit.
     *
     * @var array
     */
    const VALID_TYPES = [
        'shop'    => ['bakery', 'supermarket', 'convenience', 'deli', 'ice_cream', 'pasta', 'general'],
        'craft'   => ['caterer', 'confectionery'],
        'amenity' => ['fast_food', 'restaurant', 'cafe', 'bar', 'pub', 'ice_cream'],
    ];

    /**
     * EditorController constructor.
     *
     * @param Container $container Slim container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->api = new OsmApi();
        if (!session_id()) {
            session_start();
        }
        $this->msg = new FlashMessages();
        $this->msg->setCssClassMap([
            FlashMessages::SUCCESS => 'brdr--dark-gray p1 fnt--green',
            FlashMessages::ERROR   => 'brdr--dark-gray p1 fnt--red',
        ]);
    }

    /**
     * Display the node edit page.
     *
     * @param Request  $request  HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     */
    public function edit(Request $request, Response $response)
    {
        $type = $request->getAttribute('type');
        $feature = $this->api->getById($type, $request->getAttribute('id'));
        $this->view->render(
            $response,
            'edit.tpl',
            [
                'properties'     => $feature->getProperties(),
                'coords'         => $feature->getGeometry()->getCoordinates(),
                'id'             => $feature->getId(),
                'msg'            => $this->msg->display(null, false),
                'type'           => $type,
                'editProperties' => [
                    'diet:vegan'      => 'Vegan',
                    'diet:vegetarian' => 'Vegetarian',
                ],
            ]
        );
    }

    /**
     * Display the search page.
     *
     * @param Request  $request  HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     */
    public function search(Request $request, Response $response)
    {
        $queryString = $request->getParam('query');
        $unfilteredResults = $results = [];
        if (isset($queryString)) {
            $client = new Browser(new Curl());
            $consumer = new Consumer($client, 'https://nominatim.openstreetmap.org');
            $query = new Query();
            $query->setQuery($queryString);
            $unfilteredResults = $consumer->search($query);
        }
        foreach ($unfilteredResults as $item) {
            foreach (self::VALID_TYPES as $class => $types) {
                if ($item['class'] == $class) {
                    foreach ($types as $type) {
                        if ($item['type'] == $type) {
                            $results[] = $item;
                            break 2;
                        }
                    }
                }
            }
        }
        $this->view->render(
            $response,
            'search.tpl',
            [
                'query'   => $queryString,
                'results' => $results,
            ]
        );
    }

    /**
     * Submit an edit query.
     *
     * @param Request  $request  HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     */
    public function submit(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (is_array($params)) {
            try {
                $this->api->updateNode($request->getAttribute('type'), $request->getAttribute('id'), $params);
                $this->msg->success('Your edit has been submitted, the map will be updated shortly.', null, true);
            } catch (Exception $e) {
                $this->msg->error($e->getMessage(), null, true);
            }
        }

        return $response->withRedirect($request->getUri());
    }
}
