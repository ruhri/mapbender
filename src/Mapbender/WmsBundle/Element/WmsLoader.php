<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Mapbender\CoreBundle\Component\EntityHandler;

/**
 * WmsLoader
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmsLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wms.wmsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wms.wmsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wms.wmsloader.wms", "mb.wms.wmsloader.loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            "autoOpen" => false,
            "defaultFormat" => "image/png",
            "defaultInfoFormat" => "text/html",
            "splitLayers" => false,
            "useDeclarative" => false
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmsloader';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $files = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.wmsloader.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js'),
            'css' => array('@MapbenderWmsBundle/Resources/public/sass/element/wmsloader.scss'),
            'trans' => array('MapbenderWmsBundle:Element:wmsloader.json.twig'));
        return $files;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $req = $this->container->get('request_stack')->getCurrentRequest();
        if ($req->get('wms_url')) {
            $wms_url = $req->get('wms_url');
            $all = $req->query->all();
            foreach ($all as $key => $value) {
                if (strtolower($key) === "version" && stripos($wms_url, "version") === false) {
                    $wms_url .= "&version=" . $value;
                } else if (strtolower($key) === "request" && stripos($wms_url, "request") === false) {
                    $wms_url .= "&request=" . $value;
                } else if (strtolower($key) === "service" && stripos($wms_url, "service") === false) {
                    $wms_url .= "&service=" . $value;
                }
            }
            $configuration['wms_url'] = urldecode($wms_url);
        }
        if ($req->get('wms_id')) {
            $wmsId = $req->get('wms_id');
            $configuration['wms_id'] = $wmsId;
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $files = self::listAssets();

        $config = $this->getConfiguration();
        if (!(isset($config['useDeclarative']) && $config['useDeclarative'] === true)) {
            $idx = array_search('@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js', $files['js']);
            unset($files['js'][$idx]);
        }
        return $files;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmsBundle:ElementAdmin:wmsloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderWmsBundle:Element:wmsloader.html.twig',
                         array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'example_url' => $this->container->getParameter('wmsloader.example_url'),
                    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        //TODO ACL ACCESS
        switch ($action) {
            case 'getInstances':
                return $this->getInstances();
            case 'getCapabilities':
                return $this->getCapabilities();
            case 'signeUrl':
                return $this->signeUrl();
            case 'signeSources':
                return $this->signeSources();
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function getCapabilities()
    {
        $req = $this->container->get('request_stack')->getCurrentRequest();
        $gc_url = urldecode($req->get("url", null));
        $signer = $this->container->get('signer');
        $signedUrl = $signer->signUrl($gc_url);
        $data = $req->get('data', null);
        $path = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:entryPoint',
            'url' => urlencode($signedUrl)
        );
        $subRequest = $req->duplicate(
            array('url' => urlencode($signedUrl)), $req->request->all(), $path);
        return $this->container->get('http_kernel')->handle(
                $subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function signeUrl()
    {
        $gc_url = urldecode($this->container->get('request_stack')->getCurrentRequest()->get("url", null));
        $signer = $this->container->get('signer');
        $signedUrl = $signer->signUrl($gc_url);
        return new Response(json_encode(array("success" => $signedUrl)), 200,
                                        array('Content-Type' => 'application/json'));
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function signeSources()
    {
        $sources = json_decode($this->container->get('request_stack')->getCurrentRequest()->get("sources", "[]"), true);
        $signer = $this->container->get('signer');
        foreach ($sources as &$source) {
            $source['configuration']['options']['url'] = $signer->signUrl($source['configuration']['options']['url']);
        }
        return new Response(json_encode(array("success" => json_encode($sources))), 200,
                                                                       array('Content-Type' => 'application/json'));
    }

    /**
     * Creates Instances from sources.
     * @return array Instance configurations
     */
    protected function getInstances()
    {
        $instancesId = $this->container->get('request_stack')->getCurrentRequest()->get("instances", null);
        $instances = array();
        $instancesIds = explode(',', $instancesId);
        foreach ($instancesIds as $instanceid) {
            $securityContext = $this->container->get('security.authorization_checker');
            $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
            if (false !== $securityContext->isGranted('VIEW', $oid)) {
                $instance = $this->container->get('doctrine')->getRepository("MapbenderWmsBundle:WmsInstance")->find($instanceid);
                $entityHandler = EntityHandler::createHandler($this->container, $instance);
                $entityHandler->create(false);
                $instConfig = array(
                        'type' => $entityHandler->getEntity()->getType(),
                        'title' => $entityHandler->getEntity()->getTitle(),
                        'configuration' => $entityHandler->getConfiguration($this->container->get('signer')));
                $instances[] = $instConfig;
            }
        }
        return new Response(json_encode(array("success" => json_encode($instances))), 200,
                                                                       array('Content-Type' => 'application/json'));
    }

}
