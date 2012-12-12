<?php
namespace Mapbender\WmsBundle\Form\EventListener;

use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

class FieldSubscriber implements EventSubscriberInterface
{
    private $factory;

    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public static function getSubscribedEvents()
    {
        // Tells the dispatcher that you want to listen on the form.pre_set_data
        // event and that the preSetData method should be called.
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        $queryable = $data->getWmslayersource()->getQueryable();
        if($queryable === true){
            $form->remove('gfinfo');
            $form->add($this->factory->createNamed(
                    'gfinfo', 'checkbox', null, array(
                        'disabled' => false,
                        "required" => false)));
            $form->remove('gfinfo_default');
            $form->add($this->factory->createNamed(
                    'gfinfo_default', 'checkbox', null, array(
                        'disabled' => false,
                        "required" => false)));
        }
        $arrStyles = $data->getWmslayersource()->getStyles();
        $styleOpt = array("" => "");
        foreach ($arrStyles as $style) {
            $styleOpt[$style->getName()] = $style->getTitle();
        }
        
        $form->remove('style');
        $form->add($this->factory->createNamed(
                'style', 'choice', null, array(
                    'label' => 'style',
                    'choices' => $styleOpt,
                    "required" => false)));
    }
}