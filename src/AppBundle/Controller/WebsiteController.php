<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Website;
use AppBundle\Form\WebsiteType;

/**
 * Website controller.
 *
 * @Route("/website")
 */
class WebsiteController extends Controller {
  /**
   * Lists all Website entities.
   *
   * @Route("/", name="website_index")
   * @Method("GET")
   */
  public function indexAction(Request $request) {
    $em = $this->getDoctrine()->getManager();
    $repository =$em->getRepository('AppBundle:Website');

    // $form = $this->createForm('AppBundle\Form\WebsiteFilterType');

    $form = $this->get('form.factory')->create('AppBundle\Form\WebsiteFilterType', NULL, array(
      'action' => $this->generateUrl('website_index'),
      'method' => 'GET',
    ));

    if ($request->query->has($form->getName())) {
      // manually bind values from the request
      $form->submit($request->query->get($form->getName()));

      // initialize a query builder
      $filterBuilder = $repository->createQueryBuilder('e');

      // build the query from the given form object
      $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($form, $filterBuilder);

      $query = $filterBuilder->getQuery();
      $websites = $query->getResult();
    } else {
      $websites = $repository->findAll();
    }

    return $this->render('website/index.html.twig', array(
      'form' => $form->createView(),
      'websites' => $websites,
    ));
  }

  /**
   * Creates a new Website entity.
   *
   * @Route("/new", name="website_new")
   * @Method({"GET", "POST"})
   */
  public function newAction(Request $request) {
    $website = new Website();
    $form = $this->createForm('AppBundle\Form\WebsiteType', $website);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($website);
      $em->flush();

      return $this->redirectToRoute('website_show', array('id' => $website->getId()));
    }

    return $this->render('website/new.html.twig', array(
      'website' => $website,
      'form' => $form->createView(),
    ));
  }

  /**
   * Finds and displays a Website entity.
   *
   * @Route("/{id}", name="website_show")
   * @Method("GET")
   */
  public function showAction(Website $website){

    return $this->render('website/show.html.twig', array(
      'website' => $website,
    ));
  }

  /**
   * Displays a form to edit an existing Website entity.
   *
   * @Route("/{id}/edit", name="website_edit")
   * @Method({"GET", "POST"})
   */
  public function editAction(Request $request, Website $website) {
    $editForm = $this->createForm('AppBundle\Form\WebsiteType', $website);
    $editForm->handleRequest($request);

    if ($editForm->isSubmitted() && $editForm->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($website);
      $em->flush();

      return $this->redirectToRoute('website_edit', array('id' => $website->getId()));
    }

    return $this->render('website/edit.html.twig', array(
      'website' => $website,
      'edit_form' => $editForm->createView(),
    ));
  }


    /**
     * Creates a form to delete a Website entity.
     *
     * @param Website $website The Website entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Website $website)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('website_delete', array('id' => $website->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

}
