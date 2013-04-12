<?php

namespace Mongobox\Bundle\JukeboxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Mongobox\Bundle\JukeboxBundle\Entity\Videos;
use Mongobox\Bundle\JukeboxBundle\Entity\VideoTag;
use Mongobox\Bundle\JukeboxBundle\Entity\VideoGroup;
use Mongobox\Bundle\JukeboxBundle\Entity\Playlist;

// Forms
use Mongobox\Bundle\JukeboxBundle\Form\VideosType;
use Mongobox\Bundle\JukeboxBundle\Form\SearchVideosType;

/**
 * Videos controller.
 *
 * @Route("/videos")
 */
class VideosController extends Controller
{
    protected $_limitPagination = 50;

    /**
     * Lists all Videos entities.
     *
     * @Route("/{page}", name="videos",requirements={"page" = "\d+"}, defaults={"page" = 1})
     * @Template()
     */
    public function indexAction(Request $request, $page)
    {
        $em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('MongoboxGroupBundle:Group')->find($request->getSession()->get('id_group'));
        $videosRepository = $em->getRepository('MongoboxJukeboxBundle:Videos');
        $videoGroupRepository = $em->getRepository('MongoboxJukeboxBundle:VideoGroup');

        $formSearchVideos = $this->createForm(new SearchVideosType());

        $criteria = array();
         if (  'POST' === $request->getMethod() ) {
             $formSearchVideos->bind($request);
             $criteria = array('title' => $formSearchVideos->get('search')->getData());
         }

     	// filtre par defaut
        $filters = array('sortBy' => 'vg.lastBroadcast', 'orderBy' => 'desc');

        // $_GET parameters
        $sortBy = $request->query->get('sortBy');
        $orderBy = $request->query->get('orderBy');
        
        if( !empty($sortBy) && !empty($orderBy) ){
        	$filters = array(
        		'sortBy' => $sortBy,
        		'orderBy' => $orderBy		
        	);
        }

        $entities = $videosRepository->search(
				$group,
                $criteria,
                $page,
                $this->_limitPagination,
        		$filters
        );

        $nbPages = (int) (count($videoGroupRepository->findBy(array('group' => $group->getId())))  / $this->_limitPagination);

        $displayFilters = $filters;
        ( 'DESC' === $displayFilters['orderBy'] ) ? $displayFilters['orderBy'] = 'ASC' : $displayFilters['orderBy'] = 'DESC';
        
        return array(
            'searchVideosForm' => $formSearchVideos->createView(),
            'entities' => $entities,
            'pagination' => array(
                'page' => $page,
                'page_total' => $nbPages,
                'page_gauche' => ( $page-1 > 0 ) ? $page-1 : 1,
                'page_droite' => ( $page+1 < $nbPages ) ? $page+1 : $nbPages,
                'limite' =>  $this->_limitPagination
            ),
        	'filters' => $filters
        );
    }

    /**
     * Finds and displays a Videos entity.
     *
     * @Route("/{id}/show", name="videos_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MongoboxJukeboxBundle:Videos')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Videos entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Videos entity.
     *
     * @Route("/{id}/edit", name="videos_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MongoboxJukeboxBundle:Videos')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Videos entity.');
        }

        $editForm = $this->createForm(new VideosType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Videos entity.
     *
     * @Route("/{id}/update", name="videos_update")
     * @Method("POST")
     * @Template("MongoboxJukeboxBundle:Videos:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('MongoboxJukeboxBundle:Videos')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Videos entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new VideosType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('videos_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Videos entity.
     *
     * @Route("/{id}/delete", name="videos_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('MongoboxJukeboxBundle:Videos')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Videos entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('videos'));
    }

    /**
     * Add the video to the playlist entity.
     *
     * @Route("/{id}/add_to_playlist", name="videos_add_to_playlist")
     * @ParamConverter("video", class="MongoboxJukeboxBundle:VideoGroup")
     */
    public function addToPlaylistAction(Request $request, VideoGroup $video)
    {
        $em = $this->getDoctrine()->getManager();
		$session = $request->getSession();
		$group = $em->getRepository('MongoboxGroupBundle:Group')->find($session->get('id_group'));

        $playlist_add = new Playlist();
        $playlist_add->setVideoGroup($video);
		$playlist_add->setGroup($group);
        $playlist_add->setRandom(0);
		$playlist_add->setCurrent(0);
        $playlist_add->setDate(new \Datetime());
        $em->persist($playlist_add);
        $em->flush();

        return $this->redirect($this->generateUrl('videos'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * Action to search tags for autocomplete field
     *
     * @Route("/video-tags-ajax-autocomplete", name="video_tags_ajax_autocomplete")
     * @Template()
     */
    public function videoAjaxAutocompleteTagsAction(Request $request)
    {
        // récupération du mots clés en ajax selon la présélection du mot
        $value = $request->get('term');
        $em = $this->getDoctrine()->getManager();
        $videoTagsRepository = $em->getRepository('MongoboxJukeboxBundle:VideoTag');
        $motscles = $videoTagsRepository->getTags($value);

        return new Response(json_encode($motscles));
    }

    /**
     * Action to load tag or create it if not exist
     *
     * @Route("/video-tags-load-item", name="video_tags_load_item")
     * @Template()
     */
    public function ajaxLoadTagAction(Request $request)
    {
        // récupération du mots clés en ajax selon la présélection du mot
        $value = $request->get('tag');


        $em = $this->getDoctrine()->getManager();
        $videoTagsRepository = $em->getRepository('MongoboxJukeboxBundle:VideoTag');

        // Check if tag Already exist
        $resultTag = $videoTagsRepository->loadOneTagByName($value);
        if( false === $resultTag ){

            // Create a new tag
            $newEntityTag = new VideoTag();
            $newEntityTag
                ->setName($value)
                ->setSystemName($value)
            ;
            $em->persist($newEntityTag);
            $em->flush();

            // Parsing result
            $resultTag = array(
                'id' => $newEntityTag->getId(),
                'name' => $newEntityTag->getName()
            );
        }

        return new Response(json_encode($resultTag));
    }
}