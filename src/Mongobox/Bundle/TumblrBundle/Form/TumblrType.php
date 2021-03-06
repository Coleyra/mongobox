<?php

namespace Mongobox\Bundle\TumblrBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackValidator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;

class TumblrType extends AbstractType
{
	public function __construct($groups = array())
	{
		$this->listGroups = array();
		foreach($groups as $group)
		{
			$this->listGroups[$group->getId()] = $group->getTitle();
			if($group->getPrivate()) $this->listGroups[$group->getId()] .= ' <i class="icon-lock" title="Groupe Privé"></i>';
			else $this->listGroups[$group->getId()] .= ' <i class="icon-globe" title="Groupe Publique"></i>';
		}

	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataGroups = array();

        $entityGroups = $options['data']->getGroups();
        if( !empty($entityGroups) ){
            foreach( $entityGroups as $group ){
                $dataGroups[] =  $group->getId();
            }
        }

        $builder
            ->add('image', 'text', array(
            	'label' => 'URL image',
				'attr' => array(
                    'placeholder' => 'URL de l\'image',
                ),
				'required' => true
            ))
            ->add('text', 'text', array(
            	'label' => 'Texte',
				'attr' => array(
                    'placeholder' => 'Texte du tumblr',
                ),
				'required' => true
            ))
            ->add('addtags', 'genemu_jqueryautocompleter_entity', array(
                'route_name' => 'tumblr_tags_ajax_autocomplete',
                'class' => 'Mongobox\Bundle\TumblrBundle\Entity\TumblrTag',
                'property' => 'name',
                'label' => 'Tags',
                'attr' => array(
                    'placeholder' => 'Ajouter des tags',
                ),
                'required' => false,
                'mapped' => false
            ))
            ->add('tags', 'hidden', array(
                'mapped' => false
            ))
            ->add('groups', 'choice', array(
                'label' => 'Partager dans ces groupes',
                'choices' => $this->listGroups,
                'data' => $dataGroups,
                'multiple' => true,
                'expanded' => true,
                'mapped' => false
            ))
        ;

	}

    public function getName()
    {
        return 'tumblr';
    }
}
