<?php
/**
 * EditorController class.
 */

namespace OpenVegeMap\Editor\Controller;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use maxh\Nominatim\Exceptions\NominatimException;
use maxh\Nominatim\Nominatim;
use OpenVegeMap\Editor\OsmApi;
use Plasticbrain\FlashMessages\FlashMessages;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
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
        'shop' => ['bakery', 'supermarket', 'convenience', 'deli', 'ice_cream', 'pasta', 'general'],
        'craft' => ['caterer', 'confectionery'],
        'amenity' => ['fast_food', 'restaurant', 'cafe', 'bar', 'pub', 'ice_cream', 'biergarten'],
    ];

    /**
     * EditorController constructor.
     *
     * @param ContainerInterface $container Slim container
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
            FlashMessages::SUCCESS => 'brdr--mid-gray p1 fnt--green',
            FlashMessages::ERROR => 'brdr--mid-gray p1 fnt--red',
        ]);
    }

    /**
     * Display the node edit page.
     *
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     * @throws GuzzleException
     */
    public function edit(Request $request, Response $response): Response
    {
        $elementType = $request->getAttribute('type');

        try {
            $feature = $this->api->getById($elementType, intval($request->getAttribute('id')));
        } catch (ClientException $e) {
            return $response->withStatus(StatusCode::HTTP_NOT_FOUND)->write('This element does not exist.');
        }

        $properties = ($feature->getProperties());
        $validType = false;
        foreach (self::VALID_TYPES as $class => $types) {
            if (isset($properties[$class])) {
                foreach ($types as $type) {
                    if ($properties[$class] == $type) {
                        $validType = true;
                        break 2;
                    }
                }
            }
        }

        if (!$validType) {
            return $response->withStatus(StatusCode::HTTP_FORBIDDEN)->write('This type can not use diet tags.');
        }

        $this->view->render(
            $response,
            'edit.tpl',
            [
                'properties' => $feature->getProperties(),
                'coords' => $feature->getGeometry()->getCoordinates(),
                'id' => $feature->getId(),
                'msg' => $this->msg->display(null, false),
                'type' => $elementType,
                'editProperties' => [
                    'diet:vegan' => 'Vegan',
                    'diet:vegetarian' => 'Vegetarian',
                ],
            ]
        );

        return $response;
    }

    /**
     * Display the search page.
     *
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     * @throws GuzzleException
     * @throws NominatimException
     */
    public function search(Request $request, Response $response): Response
    {
        $queryString = $request->getParam('query');
        $unfilteredResults = $results = [];
        if (isset($queryString)) {
            $consumer = new Nominatim('https://nominatim.openstreetmap.org');
            $query = $consumer->newSearch();
            $query->query($queryString);
            $unfilteredResults = $consumer->find($query);
        }
        if (!empty($unfilteredResults)) {
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
        }
        $this->view->render(
            $response,
            'search.tpl',
            [
                'query' => $queryString,
                'results' => $results,
            ]
        );

        return $response;
    }

    /**
     * Submit an edit query.
     *
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     *
     * @return Response
     * @throws GuzzleException
     */
    public function submit(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        if (is_array($params)) {
            try {
                $this->api->updateNode($request->getAttribute('type'), intval($request->getAttribute('id')), $params);
                $this->msg->success('Your edit has been submitted, the map will be updated shortly.', null, true);
            } catch (Exception $e) {
                $this->msg->error($e->getMessage(), null, true);
            }
        }

        return $response->withRedirect($request->getUri());
    }
}
