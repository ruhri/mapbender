<?php
namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmcLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Load configurations with the element WMC Loader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wmc", "loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
            "components" => array(),
            "keepSources" => 'no',
            "keepExtent" => false,
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmcBundle\Element\Type\WmcLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmcBundle:ElementAdmin:wmcloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmcLoader';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $js = array(
            'jquery.form.js',
            'mapbender.wmchandler.js',
            'mapbender.element.wmcloader.js'
        );
        return array(
            'js' => $js,
            'css' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if (in_array("wmcidloader", $configuration['components'])) {
            $wmcid = $this->container->get('request')->get('wmcid');
            if ($wmcid) $configuration["load"] = array('wmcid' => $wmcid);
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->getConfiguration();
        $html = $this->container->get('templating')
            ->render('MapbenderWmcBundle:Element:wmcloader.html.twig',
            array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle()));
        return $html;
    }

    public function httpAction($action)
    {
        switch ($action) {
            case 'load':
                return $this->loadWmc();
                break;
            case 'loadform':
                return $this->loadForm();
                break;
            case 'list':
                return $this->getWmcList();
                break;
            case 'loadxml':
                return $this->loadXml();
                break;
            case 'wmcasxml': // TODO at client
                return $this->getWmcAsXml();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function loadWmc()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader",
                $config['components'])) {
            $wmcid = $this->container->get('request')->get("_id", null);
            $wmchandler = new WmcHandler($this, $this->application,
                $this->container);
            $wmc = $wmchandler->getWmc($wmcid, true);
            if ($wmc) {

                $id = $wmc->getId();
                return new Response(json_encode(array("data" => array($id => $wmc->getState()->getJson()))),
                    200, array('Content-Type' => 'application/json'));
            } else {
                return new Response(json_encode(array("error" => 'WMC: ' . $wmcid . ' is not found')),
                    200, array('Content-Type' => 'application/json'));
            }
        } else {
            return new Response(json_encode(array("error" => 'WMC: ' . $wmcid . ' can not be loaded- IdLoader is not allowed.')),
                200, array('Content-Type' => 'application/json'));
        }
    }

    public function loadForm()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $wmc = new Wmc();
            $form = $this->container->get("form.factory")->create(new WmcLoadType(),
                $wmc);
            $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-form.html.twig',
                array(
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId()));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            return new Response(json_encode(array(
                    "error" => 'WMC:  can not be loaded- WmcXmlLoader is not allowed.')),
                200, array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Returns a html encoded list of all wmc documents
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function getWmcList()
    {
        $response = new Response();
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader",
                $config['components'])) {
            $wmchandler = new WmcHandler($this, $this->application,
                $this->container);
            $wmclist = $wmchandler->getWmcList(true);
            $responseBody = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-list.html.twig',
                array(
                'application' => $this->application,
                'configuration' => $config,
                'id' => $this->getId(),
                'wmclist' => $wmclist)
            );
            $response->setContent($responseBody);
            return $response;
        } else {
            $response->setContent('WMC List can not be loaded- WmcListLoader is not allowed.');
            return $response;
        }
    }

    /**
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getWmcAsXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $json = $this->container->get('request')->get("state", null);
            if ($json) {
                $wmc = Wmc::create();
                $state = $wmc->getState();
                $state->setJson(json_decode($json));
                if ($state !== null && $state->getJson() !== null) {
                    $wmchandler = new WmcHandler($this, $this->application,
                        $this->container);
                    $state->setServerurl($wmchandler->getBaseUrl());
                    $state->setSlug($this->application->getSlug());
                    $state->setTitle("Mapbender State");
                    $wmc->setWmcid(round((microtime(true) * 1000)));
                    $xml = $this->container->get('templating')->render(
                        'MapbenderWmcBundle:Wmc:wmc110_simple.xml.twig',
                        array(
                        'wmc' => $wmc));
                    $response = new Response();
                    $response->setContent($xml);
                    $response->headers->set('Content-Type', 'application/xml');
                    $response->headers->set('Content-Disposition',
                        'attachment; filename=wmc.xml');
                    return $response;
                }
            }
        } else {
            return new Response(json_encode(array(
                    "error" => 'WMC:  can not be loaded- WmcXmlLoader is not allowed.')),
                200, array('Content-Type' => 'application/json'));
        }
    }

    protected function loadXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $request = $this->container->get('request');
            $wmc = Wmc::create();
            $form = $this->container->get("form.factory")->create(new WmcLoadType(),
                $wmc);
            $form->bindRequest($request);
            if ($form->isValid()) {
                if ($wmc->getXml() !== null) {
                    $file = $wmc->getXml();
                    $path = $file->getPathname();
                    $doc = WmcParser::loadDocument($path);
                    $parser = WmcParser::getParser($doc);
                    $wmc = $parser->parse();
                    if (file_exists($file->getPathname())) {
                        unlink($file->getPathname());
                    }
                    return new Response(json_encode(array("success" => array(round((microtime(true)
                                    * 1000)) => $wmc->getState()->getJson()))),
                        200, array('Content-Type' => 'application/json'));
                } else {
                    return new Response(json_encode(array(
                            "error" => 'WMC:  can not be loaded.')), 200,
                        array('Content-Type' => 'application/json'));
                }
            } else {
                return new Response(json_encode(array(
                        "error" => 'WMC:  can not be loaded.')), 200,
                    array('Content-Type' => 'application/json'));
            }
        } else {
            return new Response(json_encode(array(
                    "error" => 'WMC:  can not be loaded- WmcXmlLoader is not allowed.')),
                200, array('Content-Type' => 'application/json'));
        }
    }

}