<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Measure;
use AppBundle\Entity\Project;
use AppBundle\Entity\ProjectImage;
use AppBundle\Form\ProjectType;
use AppBundle\Form\ProjectCommentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/* // Hidden imports that may be used if the IvoryGoogleMaps library is installed
use Ivory\GoogleMap\Map;
use Ivory\GoogleMap\MapTypeId;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Overlays\Animation;
use Ivory\GoogleMap\Overlays\Icon;
use Ivory\GoogleMap\Overlays\Marker;
use Buzz\Browser;
//use Ivory\GoogleMapBundle\Model\Map;
//use Ivory\GoogleMap\Overlays\MarkerShape;
*/

class ProjectController extends Controller
{
    public function showAction(Request $request)
    {
        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);

        // Can the user edit?
        $canEdit = false;
        $deleteFormView = null;
        $deleteMeasureFormViews = array();
        if ($this->userCanEditProject($project)) {
            $canEdit = true;
            $deleteFormView = $this->createDeleteForm($project)->createView();
            foreach ($project->getMeasures() as $measure) {
                $deleteMeasureFormViews[$measure->getId()] =
                    $this->createMeasureDeleteForm($project, $measure)->createView();
            }
        }

        // Can the user comment?
        $canComment = false;
        $commentFormView = null;
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $canComment = true;
            $commentFormView = $this->createForm(ProjectCommentType::class)->createView();
        }

        return $this->render('project/project.html.twig', array(
            'project' => $project,
            'canEdit' => $canEdit,
            'canComment' => $canComment,
            'projectDeleteForm' => $deleteFormView,
            'measureDeleteForms' => $deleteMeasureFormViews,
            'commentForm' => $commentFormView,
            ));
    }

    public function createAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('Du må være logget inn og aktivert av en redaktør for å lage et prosjekt');
        }

        $em = $this->getDoctrine()->getManager();
        $project = new Project();
        // Set project description headlines to guide user input
        $project->setDescription($this->get('twig')->render('project/descriptionHeadlines.html.twig'));
        $user = $this->getUser();
        
        $flow = $this->get('ovase.form.flow.createProject'); // must match the flow's service id
        $flow->bind($project);
        $form = $flow->createForm();
        if ($flow->isValid($form)) {

            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // Form for the next step
                $form = $flow->createForm();
            } else {
                // Flow finished
                // Add project to user list and save
                $user->addProject($project);
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->persist($user);
                $em->flush();
                $flow->reset();
                return $this->redirectToRoute('project', array( 'id' => $project->getId() ));
            }
        }
        return $this->render('project/create.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
            'canEdit' => false,
        ));

    }

    public function editAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException("Du må være logget inn og aktivert av en redaktør for å se denne siden");
        }

        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);

        if (!$this->userCanEditProject($project)) {
            throw $this->createAccessDeniedException("Du har ikke redigeringsrettigheter til dette prosjektet");
        }

        $em = $this->getDoctrine()->getManager();

        // Store original images and measures to know if any were removed
        $originalImages = new ArrayCollection();
        foreach ($project->getImages() as $img)
            $originalImages->add($img);
        $originalMeasures = new ArrayCollection();
        foreach ($project->getMeasures() as $measure)
            $originalMeasures->add($measure);
        
        $flow = $this->get('ovase.form.flow.editProject');

        $flow->bind($project);
        $form = $flow->createForm();
        if ($flow->isValid($form)) {

            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // Form for the next step
                $form = $flow->createForm();
            } else {
                // Delete images and measures that were removed
                foreach ($originalImages as $origImg) {
                     if ($project->getImages()->contains($origImg) === false) {
                            $em->remove($origImg);
                    }
                }
                foreach ($originalMeasures as $origMeasure) {
                     if ($project->getMeasures()->contains($origMeasure) === false) {
                        $em->remove($origMeasure);
                    }
                }
                // Flow finished
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();

                $flow->reset(); // Remove step data from the session

                return $this->redirectToRoute('project', array( 'id' => (string)$requestID) );
            }
        }

        return $this->render('project/edit.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
        ));
    }

    public function deleteAction(Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException('Du må være logget inn');
        }

        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);

        if (!$this->getUser()->canEditProject($project) && !$this->get('security.authorization_checker')->isGranted('ROLE_EDITOR')) {
            throw $this->createAccessDeniedException("Du har ikke redigeringsrettigheter til dette prosjektet");
        }

        $form = $this->createDeleteForm($project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($project);
            $em->flush();
        }
        // TODO: Also ensure that images are removed

        return $this->redirectToRoute('projectlist');
    }

    private function userCanEditProject($project) {
        if (    $this->get('security.authorization_checker')->isGranted('ROLE_EDITOR')
             || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
             && $this->getUser()->canEditProject($project)) {
                return true;
        }
        return false;
    }

    private function createDeleteForm($project) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('delete_project', array('id' => $project->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    private function createMeasureDeleteForm($project, $measure) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('delete_measure', array(
                'measure_id' => $measure->getId(),
                'project_id' => $project->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

}
